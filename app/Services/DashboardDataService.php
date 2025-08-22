<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Contract;
use App\Models\ContractStatus;
use App\Models\LedgerEntry;
use App\Models\BankAccount;
use App\Models\Safe;
use App\Models\Investor;

class DashboardDataService
{
    public function __construct(
        private CashAccountsDataService     $cashSvc,
        private OfficeIncomeMetricsService  $officeSvc,
        private ProductAvailabilityService  $productSvc,
    ) {}

    public function build(array $filters = []): array
    {
        // 1) حالات العقود + الرسم
        [$contractsTotal, $statuses, $chartLabels, $chartData] = $this->buildContractsStatuses();

        // 2) سيولة المستثمرين (غير متأثرة بالتاريخ)
        [$invTotals, $invByInvestor] = $this->investorsLiquidityFromLedger();

        // 3) مؤشرات المكتب (حسب الفلتر لو محتاجها لعرض تفاصيل تانية)
        $officeMetrics = $this->officeSvc->build([
            'from'         => $filters['from']         ?? null,
            'to'           => $filters['to']           ?? null,
            'account_type' => $filters['account_type'] ?? null,
            'bank_ids'     => $filters['bank_ids']     ?? null,
            'safe_ids'     => $filters['safe_ids']     ?? null,
            'status_ids'   => $filters['status_ids']   ?? null,
            'types'        => $filters['types']        ?? [],
            'keywords'     => $filters['keywords']     ?? [],
        ]);

        // صافي دخل المكتب = ربح المكتب + فرق البيع + المكاتبة
        $officeNet = (float) (
            ($officeMetrics['profit']['total']   ?? 0)
          + ($officeMetrics['sales']['total']    ?? 0)
          + ($officeMetrics['mukataba']['total'] ?? 0)
        );
        $officeTotals = (object) ['net' => $officeNet];

        // 4) ملخص الحسابات (غير متأثر بالتاريخ)
        [$banksWithOpen, $safesWithOpen, $distribution] = $this->buildAccountsSection();

        // 5) السلاسل الزمنية + KPIs (تستجيب لفلتر التاريخ)
        [$timeSeries, $monthlySeries, $entriesCount, $avgAmount, $activeInvestors] = $this->buildSeriesAndKpis($filters);

        // 6) عدد البطاقات المتاح (هيتحسب في الكنترولر بدقة، هنا بس بنسيبه صفر افتراضيًا)
        $cardsAvailable = 0;

        return [
            'contractsTotal' => $contractsTotal,
            'statuses'       => $statuses,
            'chartLabels'    => $chartLabels,
            'chartData'      => $chartData,

            'invTotals'      => $invTotals,
            'invByInvestor'  => $invByInvestor,

            'officeTotals'   => $officeTotals,
            'officeMetrics'  => $officeMetrics,

            'banksWithOpen'  => $banksWithOpen,
            'safesWithOpen'  => $safesWithOpen,
            'distribution'   => $distribution,

            'timeSeries'     => $timeSeries,
            'monthlySeries'  => $monthlySeries,

            'entriesCount'   => $entriesCount,
            'avgAmount'      => $avgAmount,
            'activeInvestors'=> $activeInvestors,

            'cardsAvailable' => $cardsAvailable,
        ];
    }

    private function buildContractsStatuses(): array
    {
        $contractsTotal = Contract::count();

        $statusCounts = Contract::select('contract_status_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('contract_status_id')
            ->get();

        $statusNames = ContractStatus::pluck('name', 'id');

        $statuses = $statusCounts->map(function ($row) use ($statusNames, $contractsTotal) {
            $name = $statusNames[$row->contract_status_id] ?? 'غير محدد';
            $cnt  = (int) $row->cnt;
            $pct  = $contractsTotal > 0 ? round(($cnt / $contractsTotal) * 100, 2) : 0.0;
            return ['id'=>(int)$row->contract_status_id,'name'=>$name,'count'=>$cnt,'pct'=>$pct];
        })->sortByDesc('count')->values();

        $chartLabels = $statuses->pluck('name')->values();
        $chartData   = $statuses->pluck('count')->values();

        return [$contractsTotal, $statuses, $chartLabels, $chartData];
    }

    /**
     * سيولة المستثمرين من الدفتر فقط (استبعاد قيود المكتب)،
     * وغير متأثرة بفلتر التاريخ.
     */
    private function investorsLiquidityFromLedger(): array
    {
        $base = LedgerEntry::query()
            ->whereNotNull('investor_id')
            ->where('is_office', false); // تجاهل قيود المكتب

        $totIn  = (float) (clone $base)->where('direction','in')->sum('amount');
        $totOut = (float) (clone $base)->where('direction','out')->sum('amount');
        $invTotals = (object) [
            'inflow'  => $totIn,
            'outflow' => $totOut,
            'net'     => $totIn - $totOut,
        ];

        $rows = (clone $base)
            ->selectRaw("
                investor_id,
                SUM(CASE WHEN direction='in'  THEN amount ELSE 0 END) AS inflow,
                SUM(CASE WHEN direction='out' THEN amount ELSE 0 END) AS outflow
            ")
            ->groupBy('investor_id')
            ->get();

        $names = Investor::whereIn('id', $rows->pluck('investor_id')->filter()->unique())
            ->pluck('name','id');

        $invByInvestor = $rows->map(function ($r) use ($names) {
                $in  = (float) $r->inflow;
                $out = (float) $r->outflow;
                $net = $in - $out;
                return (object) [
                    'id'      => (int) $r->investor_id,
                    'name'    => (string) ($names[$r->investor_id] ?? ('#'.$r->investor_id)),
                    'inflow'  => $in,
                    'outflow' => $out,
                    'net'     => $net,
                ];
            })
            ->filter(fn($row) => $row->net > 0)
            ->sortByDesc('net')
            ->take(10)
            ->values();

        return [$invTotals, $invByInvestor];
    }

    /**
     * ملخص الحسابات (بنوك/خزن) + إضافة الرصيد الافتتاحي + توزيع
     * غير متأثر بفلتر التاريخ.
     */
    private function buildAccountsSection(): array
    {
        // تجاهل التاريخ هنا
        $acc = $this->cashSvc->build([
            'from' => null,
            'to'   => null,
        ]);

        $bankOpening = BankAccount::pluck('opening_balance', 'id');
        $safeOpening = Safe::pluck('opening_balance', 'id');

        $banksWithOpen = collect($acc['banks'] ?? [])->map(function (array $b) use ($bankOpening) {
            $opening = (float) ($bankOpening[$b['id']] ?? 0);
            $net     = (float) ($b['net'] ?? 0);
            return $b + ['opening_balance' => $opening, 'balance' => $opening + $net];
        });

        $safesWithOpen = collect($acc['safes'] ?? [])->map(function (array $s) use ($safeOpening) {
            $opening = (float) ($safeOpening[$s['id']] ?? 0);
            $net     = (float) ($s['net'] ?? 0);
            return $s + ['opening_balance' => $opening, 'balance' => $opening + $net];
        });

        $distribution = [
            'labels' => ['بنوك','خزن'],
            'data'   => [
                (float) $banksWithOpen->sum('balance'),
                (float) $safesWithOpen->sum('balance'),
            ],
        ];

        return [$banksWithOpen, $safesWithOpen, $distribution];
    }

    /** السلاسل الزمنية و KPIs (تتأثر بفلتر التاريخ) */
    private function buildSeriesAndKpis(array $filters): array
    {
        $base = LedgerEntry::query()
            ->when(!empty($filters['from']), fn($q) => $q->whereDate('entry_date','>=',$filters['from']))
            ->when(!empty($filters['to']),   fn($q) => $q->whereDate('entry_date','<=',$filters['to']));

        $daily = (clone $base)
            ->selectRaw("
                entry_date,
                SUM(CASE WHEN direction='in'  THEN amount ELSE 0 END) AS tin,
                SUM(CASE WHEN direction='out' THEN amount ELSE 0 END) AS tout
            ")
            ->groupBy('entry_date')
            ->orderBy('entry_date')
            ->get();

        $timeSeries = [
            'labels' => $daily->pluck('entry_date')->map(fn($d)=>(string)$d)->values()->all(),
            'in'     => $daily->pluck('tin')->map('floatval')->values()->all(),
            'out'    => $daily->pluck('tout')->map('floatval')->values()->all(),
            'net'    => $daily->map(fn($r)=>(float)$r->tin - (float)$r->tout)->values()->all(),
        ];

        $monthly = (clone $base)
            ->selectRaw("
                DATE_FORMAT(entry_date,'%Y-%m') AS ym,
                SUM(CASE WHEN direction='in'  THEN amount ELSE 0 END) AS tin,
                SUM(CASE WHEN direction='out' THEN amount ELSE 0 END) AS tout
            ")
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();

        $monthlySeries = [
            'labels' => $monthly->pluck('ym')->values()->all(),
            'in'     => $monthly->pluck('tin')->map('floatval')->values()->all(),
            'out'    => $monthly->pluck('tout')->map('floatval')->values()->all(),
        ];

        $entriesCount    = (clone $base)->count();
        $avgAmount       = (float) ((clone $base)->avg('amount') ?? 0);
        $activeInvestors = (clone $base)->whereNotNull('investor_id')->distinct('investor_id')->count('investor_id');

        return [$timeSeries, $monthlySeries, $entriesCount, $avgAmount, $activeInvestors];
    }
}
