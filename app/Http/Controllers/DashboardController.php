<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractStatus;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // ====== إجمالي العقود + توزيع الحالات ======
        $contractsTotal = Contract::count();

        $statusCounts = Contract::select('contract_status_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('contract_status_id')
            ->get();

        // أسماء الحالات مرة واحدة
        $statusNames = ContractStatus::pluck('name', 'id');

        $statuses = $statusCounts->map(function ($row) use ($statusNames, $contractsTotal) {
            $name = $statusNames[$row->contract_status_id] ?? 'غير محدد';
            $cnt  = (int) $row->cnt;
            $pct  = $contractsTotal > 0 ? round(($cnt / $contractsTotal) * 100, 2) : 0;

            return [
                'id'    => (int) $row->contract_status_id,
                'name'  => $name,
                'count' => $cnt,
                'pct'   => $pct,
            ];
        })->sortByDesc('count')->values();

        // ====== IDs لأنواع العمليات (إيداع/سحب/تحويل) ======
        $typeIds  = DB::table('transaction_types')->pluck('id', 'name');
        $TYPE_IN   = (int) ($typeIds['إيداع'] ?? -1);
        $TYPE_OUT  = (int) ($typeIds['سحب'] ?? -1);
        $TYPE_XFER = (int) ($typeIds['تحويل بين حسابات'] ?? -1);

        // ====== سيولة المستثمرين ======
        $invTotals = DB::table('investor_transactions as it')
            ->leftJoin('transaction_statuses as ts', 'ts.id', '=', 'it.status_id')
            ->selectRaw("
                COALESCE(SUM(CASE WHEN ts.transaction_type_id = {$TYPE_IN}   THEN ABS(it.amount) ELSE 0 END), 0) AS inflow,
                COALESCE(SUM(CASE WHEN ts.transaction_type_id = {$TYPE_OUT}  THEN ABS(it.amount) ELSE 0 END), 0) AS outflow,
                COALESCE(SUM(CASE WHEN ts.transaction_type_id = {$TYPE_XFER} THEN ABS(it.amount) ELSE 0 END), 0) AS transfers
            ")
            ->first();

        $invTotals->inflow    = (float)$invTotals->inflow;
        $invTotals->outflow   = (float)$invTotals->outflow;
        $invTotals->transfers = (float)$invTotals->transfers;
        $invTotals->net       = (float)$invTotals->inflow - (float)$invTotals->outflow; // التحويلات محايدة

        // أعلى 10 مستثمرين بالصافي (حساب الصافي داخل SQL + ترتيب وحدّ أعلى)
        $invByInvestor = DB::table('investor_transactions as it')
            ->join('investors as i', 'i.id', '=', 'it.investor_id')
            ->leftJoin('transaction_statuses as ts', 'ts.id', '=', 'it.status_id')
            ->selectRaw("
                i.id,
                i.name,
                COALESCE(SUM(CASE WHEN ts.transaction_type_id = {$TYPE_IN}   THEN ABS(it.amount) ELSE 0 END), 0) AS inflow,
                COALESCE(SUM(CASE WHEN ts.transaction_type_id = {$TYPE_OUT}  THEN ABS(it.amount) ELSE 0 END), 0) AS outflow,
                COALESCE(SUM(CASE WHEN ts.transaction_type_id = {$TYPE_XFER} THEN ABS(it.amount) ELSE 0 END), 0) AS transfers,
                COALESCE(SUM(
                    CASE 
                        WHEN ts.transaction_type_id = {$TYPE_IN}  THEN  ABS(it.amount)
                        WHEN ts.transaction_type_id = {$TYPE_OUT} THEN -ABS(it.amount)
                        ELSE 0
                    END
                ), 0) AS net
            ")
            ->groupBy('i.id','i.name')
            ->orderByDesc('net')
            ->limit(10)
            ->get();

        // ====== سيولة المكتب ======
        $officeTotals = DB::table('office_transactions as ot')
            ->leftJoin('transaction_statuses as ts', 'ts.id', '=', 'ot.status_id')
            ->selectRaw("
                COALESCE(SUM(CASE WHEN ts.transaction_type_id = {$TYPE_IN}   THEN ABS(ot.amount) ELSE 0 END), 0) AS inflow,
                COALESCE(SUM(CASE WHEN ts.transaction_type_id = {$TYPE_OUT}  THEN ABS(ot.amount) ELSE 0 END), 0) AS outflow,
                COALESCE(SUM(CASE WHEN ts.transaction_type_id = {$TYPE_XFER} THEN ABS(ot.amount) ELSE 0 END), 0) AS transfers
            ")
            ->first();

        $officeTotals->inflow    = (float)$officeTotals->inflow;
        $officeTotals->outflow   = (float)$officeTotals->outflow;
        $officeTotals->transfers = (float)$officeTotals->transfers;
        $officeTotals->net       = (float)$officeTotals->inflow - (float)$officeTotals->outflow;

        // ====== بيانات المخطط ======
        $chartLabels = $statuses->pluck('name')->values();
        $chartData   = $statuses->pluck('count')->values();

        return view('dashboard.index', [
            'contractsTotal' => $contractsTotal,
            'statuses'       => $statuses,
            'invTotals'      => $invTotals,
            'invByInvestor'  => $invByInvestor,
            'officeTotals'   => $officeTotals,
            'chartLabels'    => $chartLabels,
            'chartData'      => $chartData,
        ]);
    }
}
