<?php

namespace Modules\Investors\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Modules\Accounts\Entities\BankAccount;
use Modules\Accounts\Entities\Safe;
use Modules\Investors\Entities\Investor;
use Modules\Lookups\Entities\ProductType;
use Modules\Lookups\Entities\TransactionStatus;

class InvestorLedgerController extends Controller
{
    private int $CAT_INVESTORS = 1;

    public function create()
    {
        return view('ledger::ledger.create', $this->buildFormPayload([
            'pageTitleText' => 'قيد مستثمر',
            'pageHeading'   => 'إضافة قيد للمستثمرين',
            'breadcrumbParentUrl'   => route('investors.index'),
            'breadcrumbParentLabel' => __('investors::investors.Investors'),
            'cancelUrl'             => route('investors.index'),
            'showTransferLinks'     => false,
            'restrictPartyToInvestors' => true,
            'redirectRouteName'     => 'investors.index',
        ]));
    }

    public function split()
    {
        return view('ledger::ledger.split', $this->buildFormPayload([
            'pageTitleText' => 'قيد مستثمر مُجزّأ',
            'pageHeading'   => 'قيد مستثمر مُجزّأ',
            'breadcrumbParentUrl'   => route('investors.index'),
            'breadcrumbParentLabel' => __('investors::investors.Investors'),
            'cancelUrl'             => route('investors.index'),
            'showLedgerLinks'       => false,
            'restrictPartyToInvestors' => true,
            'redirectRouteName'     => 'investors.index',
        ]));
    }

    private function buildFormPayload(array $overrides = []): array
    {
        $investors = Investor::orderBy('name')->get();
        $banks     = BankAccount::orderBy('name')->get();
        $safes     = Safe::orderBy('name')->get();

        $statuses = $this->statusesForInvestors();

        $products       = ProductType::orderBy('name')->get();
        $goodsStatusIds = TransactionStatus::whereIn('name', ['شراء بضائع','بيع بضائع'])
            ->pluck('id')
            ->values()
            ->all();

        $base = [
            'investors' => $investors,
            'banks'     => $banks,
            'safes'     => $safes,
            'statusesByCategory' => [
                'investors' => $statuses,
                'office'    => collect(),
            ],
            'products'        => $products,
            'goodsStatusIds'  => $goodsStatusIds,
        ];

        return array_merge($base, $overrides);
    }

    private function statusesForInvestors(): Collection
    {
        $query = TransactionStatus::query()
            ->select(['id', 'name', 'transaction_type_id'])
            ->whereIn('id', function ($q) {
                $q->select('transaction_status_id')
                    ->from('category_transaction_status')
                    ->where('category_id', $this->CAT_INVESTORS);
            })
            ->orderBy('name');

        return $query->get()->reject(function ($status) {
            return in_array($status->name, ['فرق البيع', 'إضافة عقد', 'سداد قسط']);
        })->values();
    }
}
