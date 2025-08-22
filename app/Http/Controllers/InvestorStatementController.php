<?php

namespace App\Http\Controllers;

use App\Models\Investor;
use App\Models\Contract;
use App\Services\InvestorDataService;

class InvestorStatementController extends Controller
{
    public function show(Investor $investor, InvestorDataService $service)
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

        return view('investors.statement', [
            'investor'  => $investor,
            'data'      => $data,
            'statusMap' => $statusMap,
        ]);
    }
}
