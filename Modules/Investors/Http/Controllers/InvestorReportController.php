<?php

namespace Modules\Investors\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use Illuminate\Http\Request;
use Modules\Contracts\Entities\Contract;
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
        $rows = LedgerEntry::query()
            ->where('investor_id', $investor->id)
            ->where('direction', 'in')
            ->latest('entry_date')
            ->get();

        $total = $rows->sum('amount');

        return view('investors.reports.deposits', compact('investor', 'rows', 'total'));
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

    public function allliquidity(Request $request, InvestorDataService $svc)
    {
        $paginator = Investor::query()
            ->select(['id', 'name'])
            ->paginate(20)
            ->withQueryString();

        $liquidityByInvestor = collect();
        if ($paginator->count()) {
            $liquidityByInvestor = LedgerEntry::query()
                ->whereIn('investor_id', $paginator->pluck('id'))
                ->where('is_office', false)
                ->groupBy('investor_id')
                ->selectRaw("investor_id, COALESCE(SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END),0) - COALESCE(SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END),0) AS bal")
                ->pluck('bal', 'investor_id');
        }

        return view('investors.reports.allliquidity', [
            'paginator'           => $paginator,
            'liquidityByInvestor' => $liquidityByInvestor,
        ]);
    }
}
