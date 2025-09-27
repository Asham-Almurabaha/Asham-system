<?php

namespace App\Http\Controllers;

use App\Support\AccountAvailability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

            $account = AccountAvailability::compute($type, $id, $request->from, $request->to);

            if (! $account) {
                return response()->json(['success' => false, 'message' => 'الحساب غير موجود'], 404);
            }

            return response()->json([
                'success'  => true,
                'account'  => [
                    'id'   => $account['id'],
                    'name' => $account['name'],
                    'type' => $account['type'],
                ],
                'opening'              => $account['opening'],
                'in'                   => $account['in'],
                'out'                  => $account['out'],
                'net_movement'         => $account['in'] - $account['out'],
                'available'            => $account['available'],
                'opening_formatted'    => number_format($account['opening'], 2),
                'in_formatted'         => number_format($account['in'], 2),
                'out_formatted'        => number_format($account['out'], 2),
                'available_formatted'  => number_format($account['available'], 2),
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
                $item = AccountAvailability::compute('bank', (int) $id, $request->from, $request->to);

                if ($item) {
                    $out['banks'][] = $item;
                    $total = $this->accTotals($total, $item);
                }
            }

            foreach ($safeIds as $id) {
                $item = AccountAvailability::compute('safe', (int) $id, $request->from, $request->to);

                if ($item) {
                    $out['safes'][] = $item;
                    $total = $this->accTotals($total, $item);
                }
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

    private function accTotals(array $t, array $item): array
    {
        $t['opening']   += $item['opening'];
        $t['in']        += $item['in'];
        $t['out']       += $item['out'];
        $t['available'] += $item['available'];
        return $t;
    }
}
