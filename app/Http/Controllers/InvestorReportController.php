<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Investor;
use App\Models\LedgerEntry;
use App\Models\TransactionStatus;
use App\Services\InvestorDataService;
use Illuminate\Http\Request;

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

        return view('investors.statement', [
            'investor'  => $investor,
            'data'      => $data,
            'statusMap' => $statusMap,
        ]);
    }
    public function deposits(Investor $investor)
    {
        $currencySymbol = 'ر.س';
        
        $statusNames = ['إضافة سيولة', 'رأس المال'];

        // استعلام المسحوبات: direction = in
        $deposits = LedgerEntry::with(['status:id,name','type:id,name'])
            ->where('investor_id', $investor->id)
            ->where('direction', 'in')
            ->whereIn('transaction_status_id', function($q)use ($statusNames) {
                $q->select('id')
                    ->from('transaction_statuses')
                    ->whereIn('name', $statusNames);
            })
            ->orderByDesc('entry_date')
            ->paginate(15);

        // إجماليات
        $depositsCount = $deposits->total();
        $depositsTotal = LedgerEntry::where('investor_id', $investor->id)
            ->where('direction', 'in')
            ->whereIn('transaction_status_id', function($q)use ($statusNames) {
            $q->select('id')
                ->from('transaction_statuses')
                ->whereIn('name', $statusNames);
            })
            ->sum('amount');

        return view('investors.deposits', compact(
            'investor',
            'deposits',
            'depositsCount',
            'depositsTotal',
            'currencySymbol'
        ));
    }

    public function withdrawals(Investor $investor)
    {
        $currencySymbol = 'ر.س';

        // استعلام المسحوبات: direction = out
        $withdrawals = LedgerEntry::with(['status:id,name','type:id,name'])
            ->where('investor_id', $investor->id)
            ->where('direction', 'out')
            ->where('transaction_status_id', function($q) {
                $q->select('id')
                  ->from('transaction_statuses')
                  ->where('name', 'سحب سيولة')
                  ->limit(1);
            })
            ->orderByDesc('entry_date')
            ->paginate(15);

        // إجماليات
        $withdrawalsCount = $withdrawals->total();
        $withdrawalsTotal = LedgerEntry::where('investor_id', $investor->id)
            ->where('direction', 'out')
             ->where('transaction_status_id', function($q) {
                $q->select('id')
                  ->from('transaction_statuses')
                  ->where('name', 'سحب سيولة')
                  ->limit(1);
            })
            ->sum('amount');

        return view('investors.withdrawals', compact(
            'investor',
            'withdrawals',
            'withdrawalsCount',
            'withdrawalsTotal',
            'currencySymbol'
        ));
    }

    public function transactions(Investor $investor)
{
    $currencySymbol = 'ر.س';

    // الحالات اللي عايزينها (مع اختلافات الإملاء الشائعة)
    $statusNames = [
        'سحب سيولة', 'سحب سيوله',
        'رأس المال',
        'إضافة سيولة', 'اضافة سيولة',
    ];

    // هات IDs للحالات مرة واحدة
    $transactions = LedgerEntry::with(['status:id,name','type:id,name'])
            ->where('investor_id', $investor->id)
            ->whereIn('transaction_status_id', function($q)use ($statusNames) {
                $q->select('id')
                    ->from('transaction_statuses')
                    ->whereIn('name', $statusNames);
            })
            ->orderByDesc('entry_date')
            ->paginate(15);

    $transactionsCount = $transactions->total();
    $withdrawalsTotal = LedgerEntry::where('investor_id', $investor->id)
            ->where('direction', 'out')
             ->where('transaction_status_id', function($q) {
                $q->select('id')
                  ->from('transaction_statuses')
                  ->where('name', 'سحب سيولة')
                  ->limit(1);
            })
            ->sum('amount');

    $depositsTotal = LedgerEntry::where('investor_id', $investor->id)
            ->where('direction', 'in')
            ->whereIn('transaction_status_id', function($q)use ($statusNames) {
            $q->select('id')
                ->from('transaction_statuses')
                ->whereIn('name', $statusNames);
            })
            ->sum('amount');

    $transactionsTotal = $depositsTotal - $withdrawalsTotal ;

    return view('investors.transactions', compact(
        'investor',
        'transactions',
        'depositsTotal',
        'withdrawalsTotal',
        'transactionsCount',
        'transactionsTotal',
        'currencySymbol'
    ));
}

    
}
