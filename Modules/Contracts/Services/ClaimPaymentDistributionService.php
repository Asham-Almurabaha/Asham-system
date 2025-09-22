<?php

namespace Modules\Contracts\Services;

use App\Models\LedgerEntry;
use App\Models\OfficeTransaction;
use Carbon\Carbon;
use Modules\Accounts\Entities\BankAccount;
use Modules\Accounts\Entities\Safe;
use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Entities\ContractClaim;
use Modules\Contracts\Entities\ContractClaimPayment;
use Modules\Lookups\Entities\TransactionStatus;
use Modules\Lookups\Entities\TransactionType;

class ClaimPaymentDistributionService
{
    public function __construct(private InvestorTransactionLogger $investorTransactionLogger)
    {
    }

    public function logClaimPayment(
        Contract $contract,
        ContractClaim $claim,
        ContractClaimPayment $payment,
        float $amount,
        ?int $bankAccountId = null,
        ?int $safeId = null,
        ?string $notes = null
    ): void {
        $amount = round((float) $amount, 2);

        if ($amount <= 0) {
            return;
        }

        if (!empty($bankAccountId) && !empty($safeId)) {
            throw new \InvalidArgumentException('لا يمكن اختيار بنك وخزنة معًا في سداد المطالبة.');
        }

        $contract->loadMissing('investors');

        $accountColumns = [
            'bank_account_id' => $bankAccountId ?: null,
            'safe_id'         => $safeId ?: null,
        ];

        $bankAccountName = $bankAccountId ? BankAccount::whereKey($bankAccountId)->value('name') : null;
        $safeName        = $safeId ? Safe::whereKey($safeId)->value('name') : null;

        $accountNote = '';
        if ($bankAccountId) {
            $accountNote = ' — حساب بنكي: ' . ($bankAccountName ?: ('#' . $bankAccountId));
        } elseif ($safeId) {
            $accountNote = ' — خزنة: ' . ($safeName ?: ('#' . $safeId));
        }

        $trimmedNotes = $notes !== null ? trim((string) $notes) : '';
        $notesSuffix   = $trimmedNotes !== '' ? ' — ملاحظات: ' . $trimmedNotes : '';

        $paymentDate = $payment->paid_at
            ? ($payment->paid_at instanceof Carbon ? $payment->paid_at->copy() : Carbon::parse($payment->paid_at))
            : Carbon::now();

        $entryDate = $paymentDate->toDateString();

        $contractNumber = $contract->contract_number ?? ('#' . $contract->id);
        $claimReference = $claim->document_number ?: ('#' . $claim->id);
        $claimLabel     = "مطالبة {$claimReference}";
        $ledgerBaseNote = " — عقد #{$contractNumber} — {$claimLabel}";

        $totalInstallmentsPaid = (float) $contract->installments()->sum('payment_amount');
        $totalClaimPaymentsBefore = (float) ContractClaimPayment::query()
            ->where('id', '!=', $payment->id)
            ->whereHas('claim', function ($query) use ($contract) {
                $query->where('contract_id', $contract->id);
            })
            ->sum('amount');

        $contractTotalValue     = (float) ($contract->total_value ?? 0);
        $remainingBeforePayment = max(
            0,
            round($contractTotalValue - $totalInstallmentsPaid - $totalClaimPaymentsBefore, 2)
        );

        $amountForOutstanding = min($amount, $remainingBeforePayment);
        $excessAmount         = round($amount - $amountForOutstanding, 2);

        $investorMeta = [];
        $totalOfficeProfit = 0.0;

        foreach ($contract->investors as $investor) {
            $sharePercentage = (float) ($investor->pivot->share_percentage ?? 0);
            if ($sharePercentage <= 0) {
                continue;
            }

            $investorTotalProfit = max(0, (float) $contract->investor_profit * ($sharePercentage / 100));
            $officeSharePercent  = (float) ($investor->pivot->office_share_percentage ?? 0);
            $officeProfit        = $officeSharePercent > 0
                ? round($investorTotalProfit * ($officeSharePercent / 100), 2)
                : 0.0;

            $investorMeta[$investor->id] = [
                'office_profit' => $officeProfit,
                'share_pct'     => $sharePercentage,
                'name'          => $investor->name,
            ];

            $totalOfficeProfit = round($totalOfficeProfit + $officeProfit, 2);
        }

        if ($amountForOutstanding > 0 && empty($investorMeta)) {
            $excessAmount         = round($excessAmount + $amountForOutstanding, 2);
            $amountForOutstanding = 0.0;
        }

        $officeStatus = $this->resolveStatus(['محاماة مطالبة', 'محاماه مطالبه'], 'محاماة مطالبة');
        $officeTypeId = $officeStatus->transaction_type_id
            ?: $this->resolveTypeId('محاماة مطالبة', ['وارد', 'إيداع']);

        $officeProfitStatus = TransactionStatus::where('name', 'ربح المكتب')->first();
        if ($officeProfitStatus) {
            $officeProfitTypeId = $officeProfitStatus->transaction_type_id
                ?: $this->resolveTypeId('ربح المكتب', ['أرباح', 'تحصيل', 'وارد']);
        } else {
            $officeProfitStatus = $officeStatus;
            $officeProfitTypeId = $officeTypeId;
        }

        $officeStatusIds = array_values(array_unique(array_filter([
            $officeStatus->id,
            $officeProfitStatus->id,
        ])));

        $collectedOfficeByInvestor = OfficeTransaction::where('contract_id', $contract->id)
            ->whereIn('status_id', $officeStatusIds)
            ->selectRaw('investor_id, COALESCE(SUM(amount),0) as total')
            ->groupBy('investor_id')
            ->pluck('total', 'investor_id')
            ->toArray();

        $collectedOfficeProfit = round(array_sum($collectedOfficeByInvestor), 2);
        $remainingOfficeProfit = max(0, round($totalOfficeProfit - $collectedOfficeProfit, 2));

        $remainingToDistribute = $amountForOutstanding;
        $investorEntries       = [];

        if ($remainingToDistribute > 0 && !empty($investorMeta)) {
            $usePerInvestorDeduct = ($collectedOfficeProfit > 0) && ($remainingOfficeProfit > 0);

            if ($usePerInvestorDeduct) {
                $weights   = [];
                $sumWeight = 0.0;

                foreach ($investorMeta as $investorId => $meta) {
                    $weight = (float) $meta['share_pct'];
                    if ($weight > 0) {
                        $weights[$investorId] = $weight;
                        $sumWeight += $weight;
                    }
                }

                if ($sumWeight > 0) {
                    $allocatedSum = 0.0;
                    $ids          = array_keys($weights);
                    $lastId       = end($ids);

                    foreach ($weights as $investorId => $weight) {
                        $allocation = ($investorId === $lastId)
                            ? round($remainingToDistribute - $allocatedSum, 2)
                            : round($remainingToDistribute * $weight / $sumWeight, 2);

                        if ($allocatedSum + $allocation > $remainingToDistribute) {
                            $allocation = round($remainingToDistribute - $allocatedSum, 2);
                        }

                        $allocatedSum = round($allocatedSum + $allocation, 2);

                        if ($allocation <= 0) {
                            continue;
                        }

                        $alreadyCollected          = (float) ($collectedOfficeByInvestor[$investorId] ?? 0);
                        $investorOfficeTarget      = (float) ($investorMeta[$investorId]['office_profit'] ?? 0);
                        $officeRemainingForInvestor = max(0, round($investorOfficeTarget - $alreadyCollected, 2));

                        $officeTake   = min($allocation, $officeRemainingForInvestor);
                        $investorTake = round($allocation - $officeTake, 2);

                        if ($officeTake > 0) {
                            $officeTransaction = OfficeTransaction::create([
                                'investor_id'                => $investorId,
                                'contract_id'                => $contract->id,
                                'contract_claim_id'          => $claim->id,
                                'contract_claim_payment_id'  => $payment->id,
                                'installment_id'             => null,
                                'status_id'                  => $officeProfitStatus->id,
                                'amount'                     => $officeTake,
                                'transaction_date'           => $paymentDate,
                                'notes'                      => "تحصيل ربح المكتب من {$investorMeta[$investorId]['name']} - {$claimLabel} - العقد رقم {$contractNumber}" . ($trimmedNotes !== '' ? " - {$trimmedNotes}" : ''),
                            ]);

                            LedgerEntry::create(array_merge([
                                'entry_date'            => $entryDate,
                                'investor_id'           => null,
                                'is_office'             => true,
                                'transaction_status_id' => $officeProfitStatus->id,
                                'transaction_type_id'   => $officeProfitTypeId,
                                'contract_id'           => $contract->id,
                                'installment_id'        => null,
                                'amount'                => $officeTake,
                                'direction'             => 'in',
                                'ref'                   => 'CCP-OT-' . $payment->id . '-' . $investorId,
                                'notes'                 => 'قيد ربح المكتب' . $ledgerBaseNote . $accountNote . $notesSuffix,
                            ], $accountColumns));

                            $collectedOfficeByInvestor[$investorId] = ($collectedOfficeByInvestor[$investorId] ?? 0) + $officeTake;
                            $collectedOfficeProfit = round($collectedOfficeProfit + $officeTake, 2);
                            $remainingOfficeProfit = max(0, round($totalOfficeProfit - $collectedOfficeProfit, 2));
                        }

                        if ($investorTake > 0) {
                            $investorName   = $investorMeta[$investorId]['name'] ?? ('#' . $investorId);
                            $investorEntries[] = [
                                'investor_id'                  => $investorId,
                                'amount'                       => $investorTake,
                                'transaction_notes'            => "سداد {$claimLabel} بعد خصم المتبقّي من ربح المكتب - العقد رقم {$contractNumber}" . ($trimmedNotes !== '' ? " - {$trimmedNotes}" : ''),
                                'ledger_notes'                 => "قيد سداد مطالبة للمستثمر {$investorName}" . $ledgerBaseNote . $accountNote . $notesSuffix,
                                'transaction_date'             => $paymentDate,
                                'contract_claim_id'            => $claim->id,
                                'contract_claim_payment_id'    => $payment->id,
                            ];
                        }
                    }

                    $remainingToDistribute = max(0, round($remainingToDistribute - $allocatedSum, 2));
                }
            }

            if ($remainingToDistribute > 0 && $remainingOfficeProfit > 0) {
                $payOffice = min($remainingToDistribute, $remainingOfficeProfit);

                $weights   = [];
                $sumWeight = 0.0;

                foreach ($investorMeta as $investorId => $meta) {
                    $alreadyCollected = (float) ($collectedOfficeByInvestor[$investorId] ?? 0);
                    $remainingShare   = max(0, round(($meta['office_profit'] ?? 0) - $alreadyCollected, 2));
                    if ($remainingShare > 0) {
                        $weights[$investorId] = $remainingShare;
                        $sumWeight += $remainingShare;
                    }
                }

                if ($sumWeight > 0) {
                    $allocatedSum = 0.0;
                    $ids          = array_keys($weights);
                    $lastId       = end($ids);

                    foreach ($weights as $investorId => $weight) {
                        $allocation = ($investorId === $lastId)
                            ? round($payOffice - $allocatedSum, 2)
                            : round($payOffice * $weight / $sumWeight, 2);

                        if ($allocatedSum + $allocation > $payOffice) {
                            $allocation = round($payOffice - $allocatedSum, 2);
                        }

                        if ($allocation <= 0) {
                            continue;
                        }

                        $allocatedSum = round($allocatedSum + $allocation, 2);

                        $officeTransaction = OfficeTransaction::create([
                            'investor_id'                => $investorId,
                            'contract_id'                => $contract->id,
                            'contract_claim_id'          => $claim->id,
                            'contract_claim_payment_id'  => $payment->id,
                            'installment_id'             => null,
                            'status_id'                  => $officeProfitStatus->id,
                            'amount'                     => $allocation,
                            'transaction_date'           => $paymentDate,
                            'notes'                      => "تحصيل ربح المكتب من {$investorMeta[$investorId]['name']} - {$claimLabel} - العقد رقم {$contractNumber}" . ($trimmedNotes !== '' ? " - {$trimmedNotes}" : ''),
                        ]);

                        LedgerEntry::create(array_merge([
                            'entry_date'            => $entryDate,
                            'investor_id'           => null,
                            'is_office'             => true,
                            'transaction_status_id' => $officeProfitStatus->id,
                            'transaction_type_id'   => $officeProfitTypeId,
                            'contract_id'           => $contract->id,
                            'installment_id'        => null,
                            'amount'                => $allocation,
                            'direction'             => 'in',
                            'ref'                   => 'CCP-OT-' . $payment->id . '-' . $investorId,
                            'notes'                 => 'قيد ربح المكتب' . $ledgerBaseNote . $accountNote . $notesSuffix,
                        ], $accountColumns));

                        $collectedOfficeByInvestor[$investorId] = ($collectedOfficeByInvestor[$investorId] ?? 0) + $allocation;
                        $collectedOfficeProfit = round($collectedOfficeProfit + $allocation, 2);
                        $remainingOfficeProfit = max(0, round($totalOfficeProfit - $collectedOfficeProfit, 2));
                    }

                    $remainingToDistribute = max(0, round($remainingToDistribute - $allocatedSum, 2));
                }
            }

            if ($remainingToDistribute > 0) {
                $weights   = [];
                $sumWeight = 0.0;

                foreach ($investorMeta as $investorId => $meta) {
                    $weight = (float) $meta['share_pct'];
                    if ($weight > 0) {
                        $weights[$investorId] = $weight;
                        $sumWeight += $weight;
                    }
                }

                if ($sumWeight > 0) {
                    $allocatedSum = 0.0;
                    $ids          = array_keys($weights);
                    $lastId       = end($ids);

                    foreach ($weights as $investorId => $weight) {
                        $allocation = ($investorId === $lastId)
                            ? round($remainingToDistribute - $allocatedSum, 2)
                            : round($remainingToDistribute * $weight / $sumWeight, 2);

                        if ($allocatedSum + $allocation > $remainingToDistribute) {
                            $allocation = round($remainingToDistribute - $allocatedSum, 2);
                        }

                        if ($allocation <= 0) {
                            continue;
                        }

                        $allocatedSum = round($allocatedSum + $allocation, 2);

                        $investorName     = $investorMeta[$investorId]['name'] ?? ('#' . $investorId);
                        $investorEntries[] = [
                            'investor_id'                  => $investorId,
                            'amount'                       => $allocation,
                            'transaction_notes'            => "سداد {$claimLabel} بعد سداد كامل ربح المكتب - العقد رقم {$contractNumber}" . ($trimmedNotes !== '' ? " - {$trimmedNotes}" : ''),
                            'ledger_notes'                 => "قيد سداد مطالبة للمستثمر {$investorName}" . $ledgerBaseNote . $accountNote . $notesSuffix,
                            'transaction_date'             => $paymentDate,
                            'contract_claim_id'            => $claim->id,
                            'contract_claim_payment_id'    => $payment->id,
                        ];
                    }

                    $remainingToDistribute = max(0, round($remainingToDistribute - $allocatedSum, 2));
                }
            }
        }

        if (!empty($investorEntries)) {
            $investorStatus = $this->resolveInvestorStatus();

            $this->investorTransactionLogger->log($contract, $investorEntries, $investorStatus->name, [
                'transaction_date'    => $paymentDate,
                'installment_id'      => null,
                'bank_account_id'     => $bankAccountId,
                'safe_id'             => $safeId,
                'transaction_type_id' => $investorStatus->transaction_type_id ?: null,
                'allow_type_fallback' => true,
                'fallback_direction'  => 'in',
                'contract_claim_id'   => $claim->id,
                'contract_claim_payment_id' => $payment->id,
            ]);
        }

        if ($remainingToDistribute > 0) {
            $excessAmount = round($excessAmount + $remainingToDistribute, 2);
        }

        if ($excessAmount > 0) {
            OfficeTransaction::create([
                'investor_id'                => null,
                'contract_id'                => $contract->id,
                'contract_claim_id'          => $claim->id,
                'contract_claim_payment_id'  => $payment->id,
                'installment_id'             => null,
                'status_id'                  => $officeStatus->id,
                'amount'                     => $excessAmount,
                'transaction_date'           => $paymentDate,
                'notes'                      => "تحصيل محاماة مطالبة - {$claimLabel} - العقد رقم {$contractNumber}" . ($trimmedNotes !== '' ? " - {$trimmedNotes}" : ''),
            ]);

            LedgerEntry::create(array_merge([
                'entry_date'            => $entryDate,
                'investor_id'           => null,
                'is_office'             => true,
                'transaction_status_id' => $officeStatus->id,
                'transaction_type_id'   => $officeTypeId,
                'contract_id'           => $contract->id,
                'installment_id'        => null,
                'amount'                => $excessAmount,
                'direction'             => 'in',
                'ref'                   => 'CCP-LEGAL-' . $payment->id,
                'notes'                 => 'قيد محاماة مطالبة' . $ledgerBaseNote . $accountNote . $notesSuffix,
            ], $accountColumns));
        }
    }

    private function resolveStatus(array $names, string $context): TransactionStatus
    {
        $statuses = TransactionStatus::query()
            ->where(function ($query) use ($names) {
                foreach ($names as $name) {
                    $query->orWhere('name', $name);
                }
            })
            ->get(['id', 'name', 'transaction_type_id']);

        foreach ($names as $name) {
            $match = $statuses->firstWhere('name', $name);
            if ($match) {
                return $match;
            }
        }

        throw new \RuntimeException("تعذر العثور على حالة '{$context}'.");
    }

    private function resolveTypeId(string $main, array $alternatives = []): int
    {
        $candidates = array_merge([$main], $alternatives);

        foreach ($candidates as $candidate) {
            $id = TransactionType::where('name', $candidate)->value('id');
            if ($id) {
                return (int) $id;
            }
        }

        $fallback = TransactionType::query()->orderBy('id')->value('id');

        if (!$fallback) {
            throw new \RuntimeException('تعذر تحديد نوع الحركة المناسب.');
        }

        return (int) $fallback;
    }

    private function resolveInvestorStatus(): TransactionStatus
    {
        return $this->resolveStatus(
            ['سداد مطالبة', 'سداد مطالبه', 'سداد مطالبة للمستثمرين', 'سداد مطالبه للمستثمرين'],
            'سداد مطالبة'
        );
    }
}

