<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Contract;
use App\Models\ContractStatus;
use App\Models\LedgerEntry;
use App\Models\BankAccount;
use App\Models\Safe;
use App\Services\CashAccountsDataService;
use App\Services\OfficeIncomeMetricsService;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        CashAccountsDataService $cashSvc,
        OfficeIncomeMetricsService $officeSvc
    ) {
        // 1) حالات العقود + الرسم
        [$contractsTotal, $statuses, $chartLabels, $chartData] = $this->buildContractsStatuses();

        // 2) IDs أنواع العمليات (إيداع/سحب/تحويل)
        [$TYPE_IN, $TYPE_OUT, $TYPE_XFER] = $this->transactionTypeIds();

        // 3) سيولة المستثمرين + أعلى 10
        [$invTotals, $invByInvestor] = $this->buildInvestorsLiquidity($TYPE_IN, $TYPE_OUT, $TYPE_XFER);

        // 4) سيولة المكتب
        $officeTotals = $this->buildOfficeLiquidity($TYPE_IN, $TYPE_OUT, $TYPE_XFER);

        // 5) ملخص الحسابات (من السيرفس) + الرصيد الافتتاحي/التقديري + توزيع
        [$banksWithOpen, $safesWithOpen, $distribution] = $this->buildAccountsSection($cashSvc, $request);

        // 6) سلاسل زمنية + KPIs سريعة
        [$timeSeries, $monthlySeries, $entriesCount, $avgAmount, $activeInvestors] = $this->buildSeriesAndKpis($request);

        // 7) مؤشرات المكتب (فرق البطاقات/المكاتبة/ربح المكتب) — داخل فقط
        $officeMetrics = $officeSvc->build([
            'from' => $request->from,
            'to'   => $request->to,
            // تقدر تزود هنا: account_type / bank_ids / safe_ids / status_ids / types / keywords
        ]);

        /** @var array{
         *   contractsTotal:int,
         *   statuses:Collection<int,array{id:int,name:string,count:int,pct:float}>,
         *   chartLabels:Collection<int,string>,
         *   chartData:Collection<int,int>,
         *   invTotals:object,
         *   invByInvestor:\Illuminate\Support\Collection,
         *   officeTotals:object,
         *   banksWithOpen:\Illuminate\Support\Collection<int,array{id:int,name:string,in:float,out:float,net:float,opening_balance:float,balance:float,statuses:array}>,
         *   safesWithOpen:\Illuminate\Support\Collection<int,array{id:int,name:string,in:float,out:float,net:float,opening_balance:float,balance:float,statuses:array}>,
         *   distribution:array{labels:array<int,string>,data:array{0:float,1:float}},
         *   timeSeries:array{labels:array<int,string>,in:array<int,float>,out:array<int,float>,net:array<int,float>},
         *   monthlySeries:array{labels:array<int,string>,in:array<int,float>,out:array<int,float>},
         *   entriesCount:int, avgAmount:float, activeInvestors:int,
         *   officeMetrics:array
         * }
         */
        $vm = [
            'contractsTotal' => $contractsTotal,
            'statuses'       => $statuses,
            'chartLabels'    => $chartLabels,
            'chartData'      => $chartData,

            'invTotals'      => $invTotals,
            'invByInvestor'  => $invByInvestor,
            'officeTotals'   => $officeTotals,

            'banksWithOpen'  => $banksWithOpen,
            'safesWithOpen'  => $safesWithOpen,
            'distribution'   => $distribution,

            'timeSeries'     => $timeSeries,
            'monthlySeries'  => $monthlySeries,

            'entriesCount'   => $entriesCount,
            'avgAmount'      => $avgAmount,
            'activeInvestors'=> $activeInvestors,

            'officeMetrics'  => $officeMetrics,
        ];

        return view('dashboard.index', $vm);
    }

    /** @return array{0:int,1:\Illuminate\Support\Collection,2:\Illuminate\Support\Collection,3:\Illuminate\Support\Collection} */
    private function buildContractsStatuses(): array
    {
        $contractsTotal = Contract::count();

        $statusCounts = Contract::select('contract_status_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('contract_status_id')
            ->get();

        $statusNames = ContractStatus::pluck('name', 'id');

        /** @var Collection<int,array{id:int,name:string,count:int,pct:float}> $statuses */
        $statuses = $statusCounts->map(function ($row) use ($statusNames, $contractsTotal) {
            $name = $statusNames[$row->contract_status_id] ?? 'غير محدد';
            $cnt  = (int) $row->cnt;
            $pct  = $contractsTotal > 0 ? round(($cnt / $contractsTotal) * 100, 2) : 0;
            return ['id'=>(int)$row->contract_status_id,'name'=>$name,'count'=>$cnt,'pct'=>$pct];
        })->sortByDesc('count')->values();

        $chartLabels = $statuses->pluck('name')->values();
        $chartData   = $statuses->pluck('count')->values();

        return [$contractsTotal, $statuses, $chartLabels, $chartData];
    }

    /** @return array{0:int,1:int,2:int} */
    private function transactionTypeIds(): array
    {
        $map = DB::table('transaction_types')->pluck('id', 'name');

        $TYPE_IN   = (int) ($map['إيداع']             ?? ($map['Deposit']  ?? -1));
        $TYPE_OUT  = (int) ($map['سحب']               ?? ($map['Withdraw'] ?? -1));
        $TYPE_XFER = (int) ($map['تحويل بين حسابات'] ?? ($map['Transfer'] ?? -1));

        return [$TYPE_IN, $TYPE_OUT, $TYPE_XFER];
    }

    /** @return array{0:object,1:\Illuminate\Support\Collection} */
    private function buildInvestorsLiquidity(int $TYPE_IN, int $TYPE_OUT, int $TYPE_XFER): array
    {
        $invTotals = DB::table('investor_transactions as it')
            ->leftJoin('transaction_statuses as ts', 'ts.id', '=', 'it.status_id')
            ->selectRaw("
                COALESCE(SUM(CASE WHEN ts.transaction_type_id = ? THEN ABS(it.amount) ELSE 0 END), 0) AS inflow,
                COALESCE(SUM(CASE WHEN ts.transaction_type_id = ? THEN ABS(it.amount) ELSE 0 END), 0) AS outflow,
                COALESCE(SUM(CASE WHEN ts.transaction_type_id = ? THEN ABS(it.amount) ELSE 0 END), 0) AS transfers
            ", [$TYPE_IN, $TYPE_OUT, $TYPE_XFER])
            ->first();

        $invTotals->inflow    = (float) $invTotals->inflow;
        $invTotals->outflow   = (float) $invTotals->outflow;
        $invTotals->transfers = (float) $invTotals->transfers;
        $invTotals->net       = $invTotals->inflow - $invTotals->outflow;

        $invByInvestor = DB::table('investor_transactions as it')
            ->join('investors as i', 'i.id', '=', 'it.investor_id')
            ->leftJoin('transaction_statuses as ts', 'ts.id', '=', 'it.status_id')
            ->selectRaw("
                i.id, i.name,
                COALESCE(SUM(CASE WHEN ts.transaction_type_id = ? THEN ABS(it.amount) ELSE 0 END), 0) AS inflow,
                COALESCE(SUM(CASE WHEN ts.transaction_type_id = ? THEN ABS(it.amount) ELSE 0 END), 0) AS outflow,
                COALESCE(SUM(CASE WHEN ts.transaction_type_id = ? THEN ABS(it.amount) ELSE 0 END), 0) AS transfers,
                COALESCE(SUM(
                    CASE
                        WHEN ts.transaction_type_id = ? THEN  ABS(it.amount)
                        WHEN ts.transaction_type_id = ? THEN -ABS(it.amount)
                        ELSE 0
                    END
                ), 0) AS net
            ", [$TYPE_IN, $TYPE_OUT, $TYPE_XFER, $TYPE_IN, $TYPE_OUT])
            ->groupBy('i.id','i.name')
            ->orderByDesc('net')
            ->limit(10)
            ->get();

        return [$invTotals, $invByInvestor];
    }

    /** @return object */
    private function buildOfficeLiquidity(int $TYPE_IN, int $TYPE_OUT, int $TYPE_XFER): object
    {
        $officeTotals = DB::table('office_transactions as ot')
            ->leftJoin('transaction_statuses as ts', 'ts.id', '=', 'ot.status_id')
            ->selectRaw("
                COALESCE(SUM(CASE WHEN ts.transaction_type_id = ? THEN ABS(ot.amount) ELSE 0 END), 0) AS inflow,
                COALESCE(SUM(CASE WHEN ts.transaction_type_id = ? THEN ABS(ot.amount) ELSE 0 END), 0) AS outflow,
                COALESCE(SUM(CASE WHEN ts.transaction_type_id = ? THEN ABS(ot.amount) ELSE 0 END), 0) AS transfers
            ", [$TYPE_IN, $TYPE_OUT, $TYPE_XFER])
            ->first();

        $officeTotals->inflow    = (float) $officeTotals->inflow;
        $officeTotals->outflow   = (float) $officeTotals->outflow;
        $officeTotals->transfers = (float) $officeTotals->transfers;
        $officeTotals->net       = $officeTotals->inflow - $officeTotals->outflow;

        return $officeTotals;
    }

    /** @return array{0:\Illuminate\Support\Collection,1:\Illuminate\Support\Collection,2:array{labels:array{0:string,1:string},data:array{0:float,1:float}}} */
    private function buildAccountsSection(CashAccountsDataService $cashSvc, Request $request): array
    {
        $acc = $cashSvc->build([
            'from' => $request->from,
            'to'   => $request->to,
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

    /** @return array{0:array,1:array,2:int,3:float,4:int} */
    private function buildSeriesAndKpis(Request $request): array
    {
        $base = LedgerEntry::query()
            ->when($request->filled('from'), fn($q) => $q->whereDate('entry_date','>=',$request->from))
            ->when($request->filled('to'),   fn($q) => $q->whereDate('entry_date','<=',$request->to));

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
