<?php

namespace Modules\Investors\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Investors\Entities\Investor;
use Modules\Investors\Support\InvestorLiquidityCalculator;

class AjaxInvestorController extends Controller
{
    public function liquidity(Investor $investor, Request $request)
    {
        $summary = InvestorLiquidityCalculator::summarizeForInvestor(
            $investor->id,
            function ($query) use ($request) {
                $query
                    ->when($request->filled('from'), fn ($q) => $q->whereDate('it.transaction_date', '>=', $request->from))
                    ->when($request->filled('to'), fn ($q) => $q->whereDate('it.transaction_date', '<=', $request->to));
            }
        );

        $totIn = round((float) ($summary['in'] ?? 0), 2);
        $totOut = round((float) ($summary['out'] ?? 0), 2);
        $balance = round((float) ($summary['net'] ?? ($totIn - $totOut)), 2);

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
