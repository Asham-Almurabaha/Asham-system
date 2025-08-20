<?php

namespace App\Http\Controllers;

use App\Models\LedgerEntry;
use App\Models\BankAccount;
use App\Models\Safe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AjaxAccountController extends Controller
{
    /**
     * إرجاع المتاح في حساب محدد (بنك/خزنة)
     * GET: account_type=bank|safe , account_id , from? , to?
     */
    public function availability(Request $request)
{
    try {
        $request->validate([
            'account_type' => 'required|in:bank,safe',
            'account_id'   => 'required|integer',
            'from'         => 'nullable|date',
            'to'           => 'nullable|date',
        ]);

        $type = $request->account_type;
        $id   = (int) $request->account_id;

        $one = $this->computeForAccount($type, $id, $request->from, $request->to);
        if (!$one) {
            return response()->json(['success' => false, 'message' => 'الحساب غير موجود'], 404);
        }

        return response()->json([
            'success'  => true,
            'account'  => [
                'id'   => $one['id'],
                'name' => $one['name'],
                'type' => $one['type'],
            ],
            'opening'              => $one['opening'],
            'in'                   => $one['in'],
            'out'                  => $one['out'],
            'net_movement'         => $one['in'] - $one['out'],
            'available'            => $one['available'],
            'opening_formatted'    => number_format($one['opening'], 2),
            'in_formatted'         => number_format($one['in'], 2),
            'out_formatted'        => number_format($one['out'], 2),
            'available_formatted'  => number_format($one['available'], 2),
        ]);
    } catch (\Throwable $e) {
        \Log::error('Availability error', [
            'msg' => $e->getMessage(),
            'file'=> $e->getFile(),
            'line'=> $e->getLine(),
        ]);

        $msg = app()->hasDebugMode() && config('app.debug')
            ? $e->getMessage()
            : 'حدث خطأ غير متوقع';

        return response()->json(['success' => false, 'message' => $msg], 500);
    }
}

    /**
     * إرجاع المتاح لعدة حسابات دفعة واحدة
     */
    public function availabilityBulk(Request $request)
    {
        try {
            $bankIds = array_values(array_filter((array) $request->input('bank_ids', []), 'is_numeric'));
            $safeIds = array_values(array_filter((array) $request->input('safe_ids', []), 'is_numeric'));

            $out   = ['banks' => [], 'safes' => []];
            $total = ['opening' => 0.0, 'in' => 0.0, 'out' => 0.0, 'available' => 0.0];

            foreach ($bankIds as $id) {
                $item = $this->computeForAccount('bank', (int)$id, $request->from, $request->to);
                if ($item) { $out['banks'][] = $item; $total = $this->accTotals($total, $item); }
            }
            foreach ($safeIds as $id) {
                $item = $this->computeForAccount('safe', (int)$id, $request->from, $request->to);
                if ($item) { $out['safes'][] = $item; $total = $this->accTotals($total, $item); }
            }

            foreach ($total as $k => $v) { $total[$k] = round((float)$v, 2); }

            return response()->json(['success'=>true, 'data'=>$out, 'totals'=>$total]);
        } catch (\Throwable $e) {
            Log::error('AJAX availabilityBulk failed', [
                'route'  => 'ajax.accounts.availability.bulk',
                'params' => $request->all(),
                'error'  => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'فشل في حساب الأرصدة. راجع السجلات.',
            ], 500);
        }
    }

    // ===== Helpers =====

    private function computeForAccount(string $type, int $id, ?string $from, ?string $to): ?array
    {
        $account = $type === 'bank' ? BankAccount::find($id) : Safe::find($id);
        if (!$account) return null;

        $opening = (float) ($account->opening_balance ?? 0);

        $base = LedgerEntry::query()
            ->when($type === 'bank', fn($q) => $q->where('bank_account_id', $id)->whereNull('safe_id'))
            ->when($type === 'safe', fn($q) => $q->where('safe_id', $id)->whereNull('bank_account_id'))
            ->when($from, fn($q) => $q->whereDate('entry_date', '>=', $from))
            ->when($to,   fn($q) => $q->whereDate('entry_date', '<=', $to));

        [$sumIn, $sumOut] = $this->sumInOut($base);

        $sumIn     = round((float) $sumIn, 2);
        $sumOut    = round((float) $sumOut, 2);
        $available = round($opening + ($sumIn - $sumOut), 2);

        return [
            'id'        => (int) $account->id,
            'name'      => (string) ($account->name ?? ('#'.$account->id)),
            'type'      => $type,
            'opening'   => round($opening, 2),
            'in'        => $sumIn,
            'out'       => $sumOut,
            'available' => $available,
        ];
    }

    private function sumInOut($base): array
    {
        // 1) direction موجود؟
        if (Schema::hasColumn('ledger_entries', 'direction')) {
            $in  = (clone $base)->where('direction', 'in')->sum('amount');
            $out = (clone $base)->where('direction', 'out')->sum('amount');
            return [(float)$in, (float)$out];
        }

        // 2) transaction_types / transaction_statuses
        $TYPE_IN  = null;
        $TYPE_OUT = null;

        if (Schema::hasTable('transaction_types')) {
            $inNames  = ['إيداع','ايداع','Deposit','Incoming','In'];
            $outNames = ['سحب','Withdrawal','Outgoing','Out'];

            $TYPE_IN  = DB::table('transaction_types')->whereIn('name', $inNames)->value('id');
            $TYPE_OUT = DB::table('transaction_types')->whereIn('name', $outNames)->value('id');
        }

        $inQuery  = (clone $base);
        $outQuery = (clone $base);

        $hasTypeCol   = Schema::hasColumn('ledger_entries','transaction_type_id');
        $hasStatusCol = Schema::hasColumn('ledger_entries','transaction_status_id');
        $hasTsTable   = Schema::hasTable('transaction_statuses');

        if ($hasTypeCol || ($hasStatusCol && $hasTsTable)) {
            // داخل
            $inQuery->where(function ($q) use ($TYPE_IN, $hasTypeCol, $hasStatusCol) {
                if (!is_null($TYPE_IN) && $hasTypeCol) {
                    $q->where('transaction_type_id', $TYPE_IN);
                }
                if ($hasStatusCol) {
                    $q->orWhereIn('transaction_status_id', function ($sub) use ($TYPE_IN) {
                        $sub->select('id')->from('transaction_statuses');
                        if (!is_null($TYPE_IN) && Schema::hasColumn('transaction_statuses','transaction_type_id')) {
                            $sub->where('transaction_type_id', $TYPE_IN);
                        } else {
                            $sub->where('name','like','%إيداع%')->orWhere('name','like','%Deposit%');
                        }
                    });
                }
            });

            // خارج
            $outQuery->where(function ($q) use ($TYPE_OUT, $hasTypeCol, $hasStatusCol) {
                if (!is_null($TYPE_OUT) && $hasTypeCol) {
                    $q->where('transaction_type_id', $TYPE_OUT);
                }
                if ($hasStatusCol) {
                    $q->orWhereIn('transaction_status_id', function ($sub) use ($TYPE_OUT) {
                        $sub->select('id')->from('transaction_statuses');
                        if (!is_null($TYPE_OUT) && Schema::hasColumn('transaction_statuses','transaction_type_id')) {
                            $sub->where('transaction_type_id', $TYPE_OUT);
                        } else {
                            $sub->where('name','like','%سحب%')->orWhere('name','like','%Withdrawal%');
                        }
                    });
                }
            });

            $in  = $inQuery->sum('amount');
            $out = $outQuery->sum('amount');
            return [(float)$in, (float)$out];
        }

        // 3) fallback: موجب/سالب
        $in  = (clone $base)->where('amount','>',0)->sum('amount');
        $out = (clone $base)->where('amount','<',0)->sum(DB::raw('ABS(amount)'));

        return [(float)$in, (float)$out];
    }

    private function accTotals(array $t, array $item): array
    {
        $t['opening']   += $item['opening'];
        $t['in']        += $item['in'];
        $t['out']       += $item['out'];
        $t['available'] += $item['available'];
        return $t;
    }
}
