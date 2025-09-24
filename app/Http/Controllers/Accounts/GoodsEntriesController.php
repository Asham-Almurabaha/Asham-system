<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounts\StoreGoodsEntryRequest;
use App\Http\Requests\Accounts\StorePartialGoodsEntryRequest;
use App\Services\Accounting\GoodsEntryCreator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Modules\Accounts\Entities\BankAccount;
use Modules\Accounts\Entities\Safe;
use Modules\Lookups\Entities\ProductType;
use Modules\Lookups\Entities\TransactionStatus;

class GoodsEntriesController extends Controller
{
    public function index(): View
    {
        $banks    = BankAccount::orderBy('name')->get();
        $safes    = Safe::orderBy('name')->get();
        $products = ProductType::orderBy('name')->get();

        $status = $this->resolveStatus();
        $statusName = $status?->name ?? 'شراء بطاقات';
        $statusId = $status?->id;
        $statusType = $status ? (int) $status->transaction_type_id : null;
        $directionLabel = ($statusType === 1) ? 'داخل (إيداع)' : 'خارج (سحب)';

        $defaultTab = session('goods_active_tab', 'purchase');

        return view('accounts.entries.goods.index', compact(
            'banks',
            'safes',
            'products',
            'statusName',
            'statusId',
            'statusType',
            'directionLabel',
            'defaultTab'
        ));
    }

    public function store(StoreGoodsEntryRequest $request, GoodsEntryCreator $creator): RedirectResponse
    {
        $payload = $request->validated();
        unset($payload['active_tab']);

        try {
            $creator->createEntry($payload);
        } catch (ValidationException $exception) {
            return Redirect::back()
                ->withInput()
                ->withErrors($exception->errors(), 'goodsPurchase');
        }

        return redirect()
            ->route('accounts.entries.goods.index')
            ->with('success', 'تم حفظ قيد شراء البضائع بنجاح.')
            ->with('goods_active_tab', 'purchase');
    }

    public function storePartial(StorePartialGoodsEntryRequest $request, GoodsEntryCreator $creator): RedirectResponse
    {
        $payload = $request->validated();
        unset($payload['active_tab']);

        try {
            $creator->createPartial($payload);
        } catch (ValidationException $exception) {
            return Redirect::back()
                ->withInput()
                ->withErrors($exception->errors(), 'goodsPartial');
        }

        return redirect()
            ->route('accounts.entries.goods.index')
            ->with('success', 'تم حفظ القيد المُجزّأ للبضائع بنجاح.')
            ->with('goods_active_tab', 'partial');
    }

    private function resolveStatus(): ?TransactionStatus
    {
        return TransactionStatus::where('name', 'شراء بطاقات')->first()
            ?: TransactionStatus::where('name', 'شراء بضائع')->first();
    }
}
