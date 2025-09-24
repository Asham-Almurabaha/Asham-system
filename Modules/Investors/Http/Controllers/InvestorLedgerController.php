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

    private const TYPE_IN = 1;
    private const TYPE_OUT = 2;

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

    public function shortcuts()
    {
        return redirect()->route('investors.ledger.shortcuts.capital');
    }

    public function capitalShortcut()
    {
        return $this->renderShortcut('capital', 'investors.ledger.shortcuts.capital');
    }

    public function liquidityInShortcut()
    {
        return $this->renderShortcut('liquidity_in', 'investors.ledger.shortcuts.liquidity_in');
    }

    public function liquidityOutShortcut()
    {
        return $this->renderShortcut('liquidity_out', 'investors.ledger.shortcuts.liquidity_out');
    }

    public function zakatShortcut()
    {
        return $this->renderShortcut('zakat', 'investors.ledger.shortcuts.zakat');
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

    private function renderShortcut(string $operationKey, string $routeName)
    {
        $payload = $this->buildFormPayload();

        [$allOperations, $missingStatuses] = $this->buildShortcutOperations();

        if (!isset($allOperations[$operationKey])) {
            abort(404);
        }

        $operation = $allOperations[$operationKey];

        $shortcutLinks = [
            'capital' => [
                'label'   => __('sidebar.Investor Capital Entry'),
                'route'   => route('investors.ledger.shortcuts.capital'),
                'active'  => $operationKey === 'capital',
                'enabled' => (bool) ($allOperations['capital']['enabled'] ?? false),
            ],
            'liquidity_in' => [
                'label'   => __('sidebar.Investor Liquidity Deposit'),
                'route'   => route('investors.ledger.shortcuts.liquidity_in'),
                'active'  => $operationKey === 'liquidity_in',
                'enabled' => (bool) ($allOperations['liquidity_in']['enabled'] ?? false),
            ],
            'liquidity_out' => [
                'label'   => __('sidebar.Investor Liquidity Withdrawal'),
                'route'   => route('investors.ledger.shortcuts.liquidity_out'),
                'active'  => $operationKey === 'liquidity_out',
                'enabled' => (bool) ($allOperations['liquidity_out']['enabled'] ?? false),
            ],
            'zakat' => [
                'label'   => __('sidebar.Investor Zakat Withdrawal'),
                'route'   => route('investors.ledger.shortcuts.zakat'),
                'active'  => $operationKey === 'zakat',
                'enabled' => (bool) ($allOperations['zakat']['enabled'] ?? false),
            ],
        ];

        return view('investors::ledger.shortcuts', array_merge($payload, [
            'operations'        => [$operationKey => $operation],
            'missingStatuses'   => $missingStatuses,
            'redirectRouteName' => $routeName,
            'defaultOperation'  => $operationKey,
            'pageTitle'         => 'قيود المستثمرين — ' . ($operation['title'] ?? 'عملية سريعة'),
            'pageHeading'       => $operation['title'] ?? 'عملية سريعة',
            'shortcutLinks'     => $shortcutLinks,
        ]));
    }

    private function buildShortcutOperations(): array
    {
        $config = [
            'capital' => [
                'status_name' => 'رأس المال',
                'title'       => 'إضافة رأس مال مستثمر',
                'description' => 'سجّل أي مبالغ جديدة يضيفها المستثمر إلى رأس ماله.',
            ],
            'liquidity_in' => [
                'status_name' => 'إضافة سيولة',
                'title'       => 'إضافة سيولة مستثمر',
                'description' => 'استخدمها عند إيداع سيولة جديدة لحساب المستثمر.',
            ],
            'liquidity_out' => [
                'status_name' => 'سحب سيولة',
                'title'       => 'سحب سيولة مستثمر',
                'description' => 'سجّل أي مبالغ يتم سحبها من سيولة المستثمر المتاحة.',
            ],
            'zakat' => [
                'status_name' => 'زكاة المال',
                'title'       => 'سحب زكاة مال مستثمر',
                'description' => 'خصّص قيدًا لمسحوبات الزكاة الخاصة بالمستثمر.',
            ],
        ];

        $statusNames = collect($config)->pluck('status_name')->unique()->all();

        $statuses = TransactionStatus::query()
            ->select(['id', 'name', 'transaction_type_id'])
            ->whereIn('name', $statusNames)
            ->get()
            ->keyBy('name');

        $operations = [];
        $missing = [];

        foreach ($config as $key => $item) {
            $status = $statuses->get($item['status_name']);
            if (!$status) {
                $missing[] = $item['status_name'];
            }

            $typeId = (int) ($status->transaction_type_id ?? self::TYPE_IN);
            $directionLabel = $typeId === self::TYPE_IN ? 'داخل (إيداع)' : 'خارج (سحب)';
            $badgeClass = $typeId === self::TYPE_IN ? 'bg-success' : 'bg-danger';

            $operations[$key] = array_merge($item, [
                'key'                => $key,
                'status_id'          => $status?->id,
                'transaction_type_id'=> $typeId,
                'direction_label'    => $directionLabel,
                'badge_class'        => $badgeClass,
                'enabled'            => (bool) $status,
            ]);
        }

        return [$operations, array_values(array_unique($missing))];
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
