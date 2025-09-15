<?php

namespace Modules\Investors\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Entities\ContractStatus;
use Modules\Investors\Entities\Investor;
use Modules\Investors\Services\InvestorDataService;

class InvestorReportController extends Controller
{
    public function statement(Investor $investor, InvestorDataService $service)
    {
        // ابني كل الأرقام من الخدمة
        $data = $service->build($investor, currencySymbol: 'ر.س');

        // (اختياري) حالات العقود لعرضها في الجدول
        $contractIds = collect($data['contractBreakdown'] ?? [])->pluck('contract_id')->filter()->values();
        $statusMap = collect();
        if ($contractIds->isNotEmpty()) {
            $statusMap = Contract::query()
            ->with('contractStatus:id,name')
            ->whereIn('id', $contractIds)
            ->get(['id','contract_status_id'])
            ->mapWithKeys(function ($c) {
                $name = $c->contractStatus->name ?? null;
                if (!$name) {
                    if (!is_null($c->is_closed) && (int)$c->is_closed === 1) $name = 'مغلق';
                    elseif (!empty($c->closed_at)) $name = 'مغلق';
                    else $name = 'ساري';
                }
                return [$c->id => $name];
            });
        }

        return view('investors.reports.statement', [
            'investor'  => $investor,
            'data'      => $data,
            'statusMap' => $statusMap,
        ]);
    }

    public function deposits(Investor $investor)
    {
        $deposits = LedgerEntry::query()
            ->where('investor_id', $investor->id)
            ->where('direction', 'in')
            ->latest('entry_date')
            ->get();

        $depositsTotal = $deposits->sum('amount');

        return view('investors.reports.deposits', compact('investor', 'deposits', 'depositsTotal'));
    }

    public function withdrawals(Investor $investor)
    {
        $rows = LedgerEntry::query()
            ->where('investor_id', $investor->id)
            ->where('direction', 'out')
            ->latest('entry_date')
            ->get();

        $total = $rows->sum('amount');

        return view('investors.reports.withdrawals', compact('investor', 'rows', 'total'));
    }

    public function transactions(Investor $investor)
    {
        $rows = LedgerEntry::query()
            ->where('investor_id', $investor->id)
            ->latest('entry_date')
            ->get();

        return view('investors.reports.transactions', compact('investor', 'rows'));
    }

    public function allliquidity(Request $request)
    {
        $filters = [
            'q'        => $request->query('q'),
            'per_page' => (int) $request->query('per_page', 25),
        ];

        $query = Investor::query()
            ->select(['id', 'name'])
            ->when($filters['q'], function ($q, $v) {
                $q->where('name', 'like', "%{$v}%");
            })
            ->orderBy('name');

        $rows = $query
            ->paginate(max(1, $filters['per_page']))
            ->withQueryString();

        $currencySymbol = 'ر.س';
        $ids = $rows->pluck('id');

        $liquidityByInvestor = collect();
        $contractStats = collect();
        if ($ids->isNotEmpty()) {
            $liquidityByInvestor = LedgerEntry::query()
                ->whereIn('investor_id', $ids)
                ->where('is_office', false)
                ->groupBy('investor_id')
                ->selectRaw("investor_id, COALESCE(SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END),0) AS in_sum, COALESCE(SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END),0) AS out_sum")
                ->get()
                ->mapWithKeys(fn ($r) => [$r->investor_id => ((float) $r->in_sum - (float) $r->out_sum)]);

            $endedIds = ContractStatus::whereIn('name', [
                'مكتمل','منتهي','سداد مبكر','إلغاء','Closed','Completed','Early Settlement','Inactive'
            ])->pluck('id')->all();

            $notIn = empty($endedIds) ? '0' : implode(',', $endedIds);

            $contractStats = DB::table('contract_investor as ci')
                ->join('contracts as c', 'ci.contract_id', '=', 'c.id')
                ->whereIn('ci.investor_id', $ids)
                ->groupBy('ci.investor_id')
                ->selectRaw(
                    "ci.investor_id, " .
                    "SUM(CASE WHEN c.contract_status_id NOT IN ($notIn) THEN ci.share_value ELSE 0 END) AS initial_capital, " .
                    "SUM(CASE WHEN c.contract_status_id NOT IN ($notIn) THEN 1 ELSE 0 END) AS contracts_active, " .
                    "COUNT(*) AS contracts_total"
                )
                ->get()
                ->keyBy('investor_id');
        }

        $rows->getCollection()->transform(function ($r) use ($liquidityByInvestor, $contractStats) {
            $id = $r->id;
            $stats = $contractStats[$id] ?? null;
            $r->liquidity        = (float) ($liquidityByInvestor[$id] ?? 0);
            $r->initial_capital  = $stats ? (float) ($stats->initial_capital ?? 0) : 0.0;
            $r->contracts_active = $stats ? (int) ($stats->contracts_active ?? 0) : 0;
            $r->contracts_total  = $stats ? (int) ($stats->contracts_total ?? 0) : 0;
            return $r;
        });

        $grandTotal = (float) $rows->getCollection()->sum('liquidity');

        return view('investors.reports.allliquidity', [
            'rows'           => $rows,
            'grandTotal'     => $grandTotal,
            'filters'        => $filters,
            'currencySymbol' => $currencySymbol,
        ]);
    }
}
