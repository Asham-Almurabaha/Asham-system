<?php

namespace Modules\Contracts\Services;

use Modules\Ledger\Entities\LedgerEntry;
use App\Models\OfficeTransaction;
use Carbon\Carbon;
use Modules\Accounts\Entities\BankAccount;
use Modules\Accounts\Entities\Safe;
use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Entities\ContractInstallment;
use Modules\Lookups\Entities\TransactionStatus;
use Modules\Lookups\Entities\TransactionType;

class InstallmentPaymentDistributionService
{
    public function __construct(private InvestorTransactionLogger $investorTransactionLogger)
    {
    }

    public function logInstallmentPayment(
        Contract $contract,
        ContractInstallment $installment,
        float $amount,
        string $statusName,
        $transactionDate,
        ?int $bankAccountId = null,
        ?int $safeId = null
    ): void {
        $amount = round((float) $amount, 2);
        if ($amount <= 0) {
            return;
        }

        if (!empty($bankAccountId) && !empty($safeId)) {
            throw new \InvalidArgumentException('لا يمكن اختيار بنك وخزنة معًا في نفس العملية.');
        }

        $contract->loadMissing('investors');
        if ($contract->investors->isEmpty()) {
            return;
        }

        $accountColumns = [
            'bank_account_id' => $bankAccountId ?: null,
            'safe_id'         => $safeId ?: null,
        ];

        $accountNote = '';
        if ($bankAccountId) {
            $bankName   = optional(BankAccount::find($bankAccountId))->name;
            $accountNote = $bankName ? " | بنك: {$bankName}" : ' | بنك';
        } elseif ($safeId) {
            $safeName   = optional(Safe::find($safeId))->name;
            $accountNote = $safeName ? " | خزنة: {$safeName}" : ' | خزنة';
        }

        $officeStatusId = TransactionStatus::where('name', 'ربح المكتب')->value('id');
        if (!$officeStatusId) {
            throw new \Exception("🚫 لم يتم العثور على حالة 'ربح المكتب'");
        }

        $installmentNumber = $installment->installment_number;
        $now = $transactionDate
            ? ($transactionDate instanceof Carbon ? $transactionDate->copy() : Carbon::parse($transactionDate))
            : Carbon::now();
        $entryDate = $now->toDateString();

        $resolveTypeId = function (string $main, array $alternatives = []) {
            $id = TransactionType::where('name', $main)->value('id');
            if ($id) {
                return (int) $id;
            }

            foreach ($alternatives as $alt) {
                $id = TransactionType::where('name', $alt)->value('id');
                if ($id) {
                    return (int) $id;
                }
            }

            return (int) TransactionType::query()->orderBy('id')->value('id');
        };

        $officeTypeId = $resolveTypeId('ربح المكتب', ['أرباح', 'تحصيل', 'وارد']);

        $investorMeta = [];

        foreach ($contract->investors as $investor) {
            $sharePercentage = (float) ($investor->pivot->share_percentage ?? 0);
            if ($sharePercentage <= 0) {
                continue;
            }

            $investorTotalProfit   = max(0, (float) $contract->investor_profit * ($sharePercentage / 100));
            $officeSharePercentage = (float) ($investor->pivot->office_share_percentage ?? 0);

            $officeProfit = $officeSharePercentage > 0
                ? round($investorTotalProfit * ($officeSharePercentage / 100), 2)
                : 0.0;

            $investorMeta[$investor->id] = [
                'office_profit' => $officeProfit,
                'share_pct'     => $sharePercentage,
                'name'          => $investor->name,
            ];
        }

        if (empty($investorMeta)) {
            return;
        }

        $collectedOfficeByInvestor = OfficeTransaction::where('contract_id', $contract->id)
            ->where('status_id', $officeStatusId)
            ->selectRaw('investor_id, COALESCE(SUM(amount),0) as total')
            ->groupBy('investor_id')
            ->pluck('total', 'investor_id')
            ->toArray();


        $weights = [];
        $sumWeights = 0.0;

        foreach ($investorMeta as $id => $meta) {
            $weight = (float) $meta['share_pct'];
            if ($weight > 0) {
                $weights[$id] = $weight;
                $sumWeights += $weight;
            }
        }

        if ($sumWeights <= 0) {
            return;
        }

        $allocatedSum = 0.0;
        $ids = array_keys($weights);
        $lastId = end($ids);

        $pendingInvestorEntries = [];
        $totalOfficeTake = 0.0;

        foreach ($weights as $investorId => $weight) {
            $allocation = ($investorId === $lastId)
                ? round($amount - $allocatedSum, 2)
                : round($amount * $weight / $sumWeights, 2);

            if ($allocatedSum + $allocation > $amount) {
                $allocation = round($amount - $allocatedSum, 2);
            }

            $allocatedSum = round($allocatedSum + $allocation, 2);
            if ($allocation <= 0) {
                continue;
            }

            $alreadyCollected = (float) ($collectedOfficeByInvestor[$investorId] ?? 0);
            $investorOfficeTarget = (float) ($investorMeta[$investorId]['office_profit'] ?? 0);
            $officeRemainingForInvestor = max(0, round($investorOfficeTarget - $alreadyCollected, 2));

            $officeTake   = min($allocation, $officeRemainingForInvestor);
            $investorTake = round($allocation - $officeTake, 2);

            if ($officeTake > 0) {
                $officeTransaction = OfficeTransaction::create([
                    'investor_id'      => $investorId,
                    'contract_id'      => $contract->id,
                    'installment_id'   => $installment->id,
                    'status_id'        => $officeStatusId,
                    'amount'           => $officeTake,
                    'transaction_date' => $now,
                    'notes'            => "تحصيل ربح المكتب من {$investorMeta[$investorId]['name']}"
                        . ($installmentNumber ? " - قسط رقم {$installmentNumber}" : '')
                        . " - العقد رقم {$contract->contract_number}",
                ]);

                if ($officeTypeId) {
                    LedgerEntry::create(array_merge([
                        'entry_date'            => $entryDate,
                        'investor_id'           => null,
                        'is_office'             => true,
                        'transaction_status_id' => $officeStatusId,
                        'transaction_type_id'   => $officeTypeId,
                        'contract_id'           => $contract->id,
                        'installment_id'        => $installment->id,
                        'amount'                => $officeTake,
                        'ref'                   => 'OT-' . $officeTransaction->id,
                        'notes'                 => "قيد ربح المكتب — عقد #{$contract->contract_number}"
                            . ($installmentNumber ? " — قسط #{$installmentNumber}" : '')
                            . $accountNote,
                    ], $accountColumns));
                }

                $totalOfficeTake = round($totalOfficeTake + $officeTake, 2);
            }

            if ($investorTake > 0) {
                $pendingInvestorEntries[] = [
                    'investor_id' => $investorId,
                    'amount'      => $investorTake,
                    'name'        => $investorMeta[$investorId]['name'] ?? ('#' . $investorId),
                ];
            }
        }

        if (empty($pendingInvestorEntries)) {
            return;
        }

        $noteSuffix = $totalOfficeTake > 0
            ? 'بعد خصم المتبقّي من ربح المكتب'
            : 'بعد سداد كامل ربح المكتب';

        $transactionPrefix = $installmentNumber
            ? "سداد قسط رقم {$installmentNumber}"
            : 'سداد';

        $investorEntries = [];
        foreach ($pendingInvestorEntries as $entry) {
            $investorEntries[] = [
                'investor_id'       => $entry['investor_id'],
                'amount'            => $entry['amount'],
                'transaction_notes' => $transactionPrefix . ' ' . $noteSuffix
                    . " - العقد رقم {$contract->contract_number}",
                'ledger_notes'      => "قيد سداد قسط للمستثمر {$entry['name']} — عقد #{$contract->contract_number}"
                    . ($installmentNumber ? " — قسط #{$installmentNumber}" : '')
                    . $accountNote,
            ];
        }

        if (!empty($investorEntries)) {
            $this->investorTransactionLogger->log($contract, $investorEntries, $statusName, [
                'transaction_date'    => $now,
                'installment_id'      => $installment->id,
                'bank_account_id'     => $bankAccountId,
                'safe_id'             => $safeId,
                'allow_type_fallback' => true,
                'fallback_direction'  => 'in',
            ]);
        }
    }
}

