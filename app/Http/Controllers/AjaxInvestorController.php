<?php

namespace App\Http\Controllers;

use App\Models\Investor;
use App\Models\LedgerEntry;
use Illuminate\Http\Request;

class AjaxInvestorController extends Controller
{
    public function liquidity(Investor $investor, Request $request)
    {
        // قيود المستثمر فقط (مش المكتب)
        $base = LedgerEntry::query()
            ->where('is_office', false)
            ->where('investor_id', $investor->id)
            // (اختياري) فلترة بنوع الحساب
            ->when($request->filled('account_type'), function ($q) use ($request) {
                if ($request->account_type === 'bank') {
                    $q->whereNotNull('bank_account_id')->whereNull('safe_id');
                } elseif ($request->account_type === 'safe') {
                    $q->whereNotNull('safe_id')->whereNull('bank_account_id');
                }
            })
            // (اختياري) فلترة بالتواريخ لو حابب
            ->when($request->filled('from'), fn ($q) => $q->whereDate('entry_date', '>=', $request->from))
            ->when($request->filled('to'),   fn ($q) => $q->whereDate('entry_date', '<=', $request->to));

        // إجمالي الداخل = كل قيد نوعه (أو حالة نوعها) إيداع (1)
        $totIn = (clone $base)
            ->where(function ($q) {
                $q->where('transaction_type_id', 1) // إيداع
                  ->orWhereIn('transaction_status_id', function ($sub) {
                      $sub->select('id')
                          ->from('transaction_statuses')
                          ->where('transaction_type_id', 1);
                  });
            })
            ->sum('amount');

        // إجمالي الخارج = كل قيد نوعه (أو حالة نوعها) سحب (2)
        $totOut = (clone $base)
            ->where(function ($q) {
                $q->where('transaction_type_id', 2) // سحب
                  ->orWhereIn('transaction_status_id', function ($sub) {
                      $sub->select('id')
                          ->from('transaction_statuses')
                          ->where('transaction_type_id', 2);
                  });
            })
            ->sum('amount');

        // الترصيد
        $totIn   = round((float) $totIn, 2);
        $totOut  = round((float) $totOut, 2);
        $balance = round($totIn - $totOut, 2);

        return response()->json([
            'success'        => true,
            'in'             => $totIn,
            'out'            => $totOut,
            'cash'           => $balance,               // اسم متوافق مع الواجهة
            'balance'        => $balance,               // اسم قديم لو بتستخدمه
            'in_formatted'   => number_format($totIn, 2),
            'out_formatted'  => number_format($totOut, 2),
            'formatted'      => number_format($balance, 2),
        ]);
    }
}
