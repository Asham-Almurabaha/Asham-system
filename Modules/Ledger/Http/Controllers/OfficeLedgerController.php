<?php

namespace Modules\Ledger\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Accounts\Entities\BankAccount;
use Modules\Accounts\Entities\Safe;
use Modules\Investors\Entities\Investor;
use Modules\Lookups\Entities\TransactionStatus;
use function array_key_first;
use function view;

class OfficeLedgerController extends Controller
{
    private int $CAT_OFFICE = 4;

    private const TYPE_IN  = 1;
    private const TYPE_OUT = 2;

    public function shortcuts()
    {
        [$operations] = $this->buildOperations();

        $preferredOrder = ['mukataba', 'sales_diff', 'account_deposit', 'account_withdrawal', 'opening_balance'];
        foreach ($preferredOrder as $key) {
            if (isset($operations[$key])) {
                return redirect()->route($this->routeForOperation($key));
            }
        }

        if ($operations) {
            $firstKey = array_key_first($operations);
            return redirect()->route($this->routeForOperation($firstKey));
        }

        abort(404);
    }

    public function mukataba()
    {
        return $this->renderShortcut('mukataba', $this->routeForOperation('mukataba'));
    }

    public function salesDiff()
    {
        return $this->renderShortcut('sales_diff', $this->routeForOperation('sales_diff'));
    }

    public function accountDeposit()
    {
        return $this->renderShortcut('account_deposit', $this->routeForOperation('account_deposit'));
    }

    public function accountWithdrawal()
    {
        return $this->renderShortcut('account_withdrawal', $this->routeForOperation('account_withdrawal'));
    }

    public function openingBalance()
    {
        return $this->renderShortcut('opening_balance', $this->routeForOperation('opening_balance'));
    }

    private function renderShortcut(string $operationKey, string $routeName)
    {
        [$operations, $missingStatuses] = $this->buildOperations();

        if (!isset($operations[$operationKey])) {
            abort(404);
        }

        $operation = $operations[$operationKey];

        $banks     = BankAccount::orderBy('name')->get();
        $safes     = Safe::orderBy('name')->get();
        $investors = Investor::orderBy('name')->get();

        $shortcutLinks = [
            'mukataba' => [
                'label'   => 'قيد المكاتبة',
                'route'   => route($this->routeForOperation('mukataba')),
                'active'  => $operationKey === 'mukataba',
                'enabled' => isset($operations['mukataba']) ? ($operations['mukataba']['enabled'] ?? false) : false,
            ],
            'sales_diff' => [
                'label'   => 'قيد فرق البيع',
                'route'   => route($this->routeForOperation('sales_diff')),
                'active'  => $operationKey === 'sales_diff',
                'enabled' => isset($operations['sales_diff']) ? ($operations['sales_diff']['enabled'] ?? false) : false,
            ],
            'account_deposit' => [
                'label'   => 'إيداع حسابات المكتب',
                'route'   => route($this->routeForOperation('account_deposit')),
                'active'  => $operationKey === 'account_deposit',
                'enabled' => isset($operations['account_deposit']) ? ($operations['account_deposit']['enabled'] ?? false) : false,
            ],
            'account_withdrawal' => [
                'label'   => 'سحب حسابات المكتب',
                'route'   => route($this->routeForOperation('account_withdrawal')),
                'active'  => $operationKey === 'account_withdrawal',
                'enabled' => isset($operations['account_withdrawal']) ? ($operations['account_withdrawal']['enabled'] ?? false) : false,
            ],
            'opening_balance' => [
                'label'   => 'رصيد افتتاحي للمكتب',
                'route'   => route($this->routeForOperation('opening_balance')),
                'active'  => $operationKey === 'opening_balance',
                'enabled' => isset($operations['opening_balance']) ? ($operations['opening_balance']['enabled'] ?? false) : false,
            ],
        ];

        return view('ledger::office.shortcuts', [
            'operations'        => [$operationKey => $operation],
            'missingStatuses'   => $missingStatuses,
            'redirectRouteName' => $routeName,
            'defaultOperation'  => $operationKey,
            'pageTitle'         => 'قيود المكتب — ' . ($operation['title'] ?? 'عملية سريعة'),
            'pageHeading'       => $operation['title'] ?? 'عملية سريعة',
            'banks'             => $banks,
            'safes'             => $safes,
            'investors'         => $investors,
            'shortcutLinks'     => $shortcutLinks,
        ]);
    }

    private function buildOperations(): array
    {
        $config = [
            'mukataba' => [
                'status_name'   => 'المكاتبة',
                'title'         => 'تحصيل المكاتبة للمكتب',
                'description'   => 'سجّل المبالغ المحصلة من المكاتبات لصالح المكتب.',
                'allow_investor'=> false,
            ],
            'sales_diff' => [
                'status_name'   => 'فرق البيع',
                'title'         => 'قيد فرق البيع للمكتب',
                'description'   => 'سجّل فروقات البيع التي تخص المكتب.',
                'allow_investor'=> false,
            ],
            'account_deposit' => [
                'status_name'   => 'إيداع حسابات',
                'title'         => 'إيداع حسابات المكتب',
                'description'   => 'سجّل الإيداعات المباشرة في الحسابات البنكية أو الخزن الخاصة بالمكتب.',
                'allow_investor'=> false,
            ],
            'account_withdrawal' => [
                'status_name'   => 'سحب حسابات',
                'title'         => 'سحب حسابات المكتب',
                'description'   => 'سجّل أي مبالغ يتم سحبها من حسابات المكتب.',
                'allow_investor'=> false,
            ],
            'opening_balance' => [
                'status_name'   => 'رصيد افتتاحي',
                'title'         => 'رصيد افتتاحي للمكتب',
                'description'   => 'سجّل رصيد البداية لحسابات المكتب عند بدء الاستخدام.',
                'allow_investor'=> false,
            ],
        ];

        $statusNames = collect($config)->pluck('status_name')->unique()->all();

        $statuses = TransactionStatus::query()
            ->select(['id', 'name', 'transaction_type_id'])
            ->whereIn('name', $statusNames)
            ->get()
            ->keyBy('name');

        $operations = [];
        $missing    = [];

        foreach ($config as $key => $item) {
            $status = $statuses->get($item['status_name']);
            if (!$status) {
                $missing[] = $item['status_name'];
                continue;
            }

            $allowed = DB::table('category_transaction_status')
                ->where('category_id', $this->CAT_OFFICE)
                ->where('transaction_status_id', $status->id)
                ->exists();

            $typeId = (int) $status->transaction_type_id;

            $operations[$key] = [
                'status_id'          => (int) $status->id,
                'status_name'        => $status->name,
                'transaction_type_id'=> $typeId,
                'title'              => $item['title'] ?? $status->name,
                'description'        => $item['description'] ?? null,
                'badge_class'        => $this->badgeClassForType($typeId),
                'direction_label'    => $this->directionLabelForType($typeId),
                'enabled'            => $allowed,
                'allow_investor'     => (bool) ($item['allow_investor'] ?? false),
            ];
        }

        return [$operations, $missing];
    }

    private function badgeClassForType(int $typeId): string
    {
        return match ($typeId) {
            self::TYPE_IN  => 'bg-success',
            self::TYPE_OUT => 'bg-danger',
            default        => 'bg-secondary',
        };
    }

    private function directionLabelForType(int $typeId): string
    {
        return match ($typeId) {
            self::TYPE_IN  => 'داخل',
            self::TYPE_OUT => 'خارج',
            default        => 'داخل/خارج',
        };
    }

    private function routeForOperation(string $operationKey): string
    {
        return match ($operationKey) {
            'mukataba'         => 'ledger.office.shortcuts.mukataba',
            'sales_diff'       => 'ledger.office.shortcuts.sales_diff',
            'account_deposit'  => 'ledger.office.shortcuts.account_deposit',
            'account_withdrawal'=> 'ledger.office.shortcuts.account_withdrawal',
            'opening_balance'  => 'ledger.office.shortcuts.opening_balance',
            default            => 'ledger.office.shortcuts',
        };
    }
}
