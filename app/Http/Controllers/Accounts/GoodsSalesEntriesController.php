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

class GoodsSalesEntriesController extends Controller
{
    public function index(): View
    {
        $banks    = BankAccount::orderBy('name')->get();
        $safes    = Safe::orderBy('name')->get();
        $products = ProductType::orderBy('name')->get();

        $status = $this->resolveStatus();
        $statusName = $status?->name ?? 'بيع بضائع';
        $statusId = $status?->id;
        $statusType = $status ? (int) $status->transaction_type_id : null;
        $directionLabel = ($statusType === 1) ? 'داخل (إيداع)' : 'خارج (سحب)';

        $defaultTab = session('goods_sales_active_tab', 'purchase');

        $primaryTabLabel = 'قيد بيع بضائع';
        $primaryFormAction = route('accounts.entries.goods.sales.store');
        $partialFormAction = route('accounts.entries.goods.sales.store-partial');

        $pageTitle = 'القيود — بيع البضائع';
        $pageHeading = 'قيود بيع البضائع';

        return view('accounts.entries.goods.index', compact(
            'banks',
            'safes',
            'products',
            'statusName',
            'statusId',
            'statusType',
            'directionLabel',
            'defaultTab',
            'primaryTabLabel',
            'primaryFormAction',
            'partialFormAction',
            'pageTitle',
            'pageHeading'
        ));
    }

    public function store(StoreGoodsEntryRequest $request, GoodsEntryCreator $creator): RedirectResponse
    {
        $payload = $request->validated();
        unset($payload['active_tab']);

        if (empty($payload['status_id'])) {
            $status = $this->resolveStatus();
            if ($status) {
                $payload['status_id'] = $status->id;
            }
        }

        try {
            $creator->createEntry($payload);
        } catch (ValidationException $exception) {
            return Redirect::back()
                ->withInput()
                ->withErrors($exception->errors(), 'goodsPurchase');
        }

        return redirect()
            ->route('accounts.entries.goods.sales.index')
            ->with('success', 'تم حفظ قيد بيع البضائع بنجاح.')
            ->with('goods_sales_active_tab', 'purchase');
    }

    public function storePartial(StorePartialGoodsEntryRequest $request, GoodsEntryCreator $creator): RedirectResponse
    {
        $payload = $request->validated();
        unset($payload['active_tab']);

        if (empty($payload['status_id'])) {
            $status = $this->resolveStatus();
            if ($status) {
                $payload['status_id'] = $status->id;
            }
        }

        try {
            $creator->createPartial($payload);
        } catch (ValidationException $exception) {
            return Redirect::back()
                ->withInput()
                ->withErrors($exception->errors(), 'goodsPartial');
        }

        return redirect()
            ->route('accounts.entries.goods.sales.index')
            ->with('success', 'تم حفظ القيد المُجزّأ لبيع البضائع بنجاح.')
            ->with('goods_sales_active_tab', 'partial');
    }

    private function resolveStatus(): ?TransactionStatus
    {
        return TransactionStatus::where('name', 'بيع بضائع')->first();
    }
}
