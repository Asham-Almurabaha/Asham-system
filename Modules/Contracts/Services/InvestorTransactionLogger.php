<?php

namespace Modules\Contracts\Services;

use App\Models\LedgerEntry;
use App\Models\Lookups\TransactionStatus;
use App\Models\Lookups\TransactionType;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Support\TransactionDirection;
use Modules\Investors\Entities\InvestorTransaction;

class InvestorTransactionLogger
{
    /** @var array<string, \App\Models\Lookups\TransactionStatus> */
    private array $statusCache = [];

    /** @var array<string, int|null> */
    private array $typeCache = [];

    /** @var array<int, string|null> */
    private array $typeNameCache = [];

    /** @var array<int, string|null> */
    private array $directionCache = [];

    /** @var array<string, array<int, string>> */
    private array $typeSynonyms = [
        'إضافة عقد'   => ['استثمار عقد', 'حركة مستثمر', 'عقد جديد'],
        'توزيع أرباح' => ['أرباح', 'حركة مستثمر'],
        'سداد أصل'    => ['سداد أصل', 'تحصيل'],
        'سداد قسط'    => ['تحصيل قسط', 'تحصيل', 'وارد', 'ايداع', 'إيداع'],
    ];

    /**
     * @param array<int, array<string, mixed>> $entries
     * @param array<string, mixed> $options
     */
    public function log(Contract $contract, array $entries, string $statusName, array $options = []): void
    {
        if (empty($entries)) {
            return;
        }

        $status = $this->getStatusByName($statusName);

        $allowTypeFallback = (bool) ($options['allow_type_fallback'] ?? false);
        $typeId = $options['transaction_type_id']
            ?? $this->resolveTypeId($statusName, $status->transaction_type_id, $allowTypeFallback);

        if (!$typeId) {
            throw new \RuntimeException("تعذّر تحديد نوع الحركة للحالة '{$statusName}'.");
        }

        $direction = $options['direction']
            ?? $this->resolveDirection($typeId)
            ?? ($options['fallback_direction'] ?? null);

        if (!in_array($direction, ['in', 'out'], true)) {
            $typeName = $this->getTransactionTypeName($typeId) ?? ('#' . $typeId);
            throw new \RuntimeException(
                "تعذّر استنتاج الاتجاه من اسم النوع '{$typeName}'. عدّل اسم النوع ليشمل (ايداع/سحب) أو أضف مرادفات في الدالة."
            );
        }

        $defaultTransactionDate = $this->normalizeDateTime($options['transaction_date'] ?? null);
        $defaultInstallmentId   = $options['installment_id'] ?? null;
        $defaultTxnNotes        = $options['transaction_notes'] ?? null;
        $defaultLedgerNotes     = $options['ledger_notes'] ?? null;
        $bankAccountId          = $options['bank_account_id'] ?? null;
        $safeId                 = $options['safe_id'] ?? null;

        DB::transaction(function () use (
            $entries,
            $contract,
            $status,
            $statusName,
            $typeId,
            $direction,
            $defaultTransactionDate,
            $defaultInstallmentId,
            $defaultTxnNotes,
            $defaultLedgerNotes,
            $bankAccountId,
            $safeId
        ) {
            foreach ($entries as $entry) {
                $investorId = (int) ($entry['investor_id'] ?? $entry['id'] ?? 0);
                $amount     = (float) ($entry['amount'] ?? $entry['share_value'] ?? 0);

                if ($investorId <= 0 || $amount <= 0) {
                    continue;
                }

                $transactionDate = $this->normalizeDateTime($entry['transaction_date'] ?? $defaultTransactionDate);
                $installmentId   = $entry['installment_id'] ?? $defaultInstallmentId;

                $transactionNotes = $entry['transaction_notes']
                    ?? $defaultTxnNotes
                    ?? $this->defaultTransactionNote($contract, $statusName);

                $ledgerNotes = $entry['ledger_notes']
                    ?? $defaultLedgerNotes
                    ?? $this->defaultLedgerNote($contract, $statusName, $investorId);

                $entryBankAccountId = $entry['bank_account_id'] ?? $bankAccountId;
                $entrySafeId        = $entry['safe_id'] ?? $safeId;

                $transaction = InvestorTransaction::create([
                    'investor_id'      => $investorId,
                    'contract_id'      => $contract->id,
                    'installment_id'   => $installmentId,
                    'status_id'        => $status->id,
                    'amount'           => $amount,
                    'transaction_date' => $transactionDate,
                    'notes'            => $transactionNotes,
                ]);

                LedgerEntry::create([
                    'entry_date'            => $transactionDate->toDateString(),
                    'investor_id'           => $investorId,
                    'is_office'             => false,
                    'transaction_status_id' => $status->id,
                    'transaction_type_id'   => $typeId,
                    'bank_account_id'       => $entryBankAccountId,
                    'safe_id'               => $entrySafeId,
                    'contract_id'           => $contract->id,
                    'installment_id'        => $installmentId,
                    'amount'                => $amount,
                    'direction'             => $direction,
                    'ref'                   => 'IT-' . $transaction->id,
                    'notes'                 => $ledgerNotes,
                ]);
            }
        });
    }

    private function getStatusByName(string $statusName): TransactionStatus
    {
        $key = mb_strtolower(trim($statusName));

        if (!array_key_exists($key, $this->statusCache)) {
            $status = TransactionStatus::where('name', $statusName)
                ->first(['id', 'transaction_type_id']);

            if (!$status) {
                throw new \RuntimeException("الحالة '{$statusName}' غير موجودة.");
            }

            $this->statusCache[$key] = $status;
        }

        return $this->statusCache[$key];
    }

    private function resolveTypeId(string $statusName, ?int $statusTypeId, bool $allowFallback): ?int
    {
        if ($statusTypeId) {
            return (int) $statusTypeId;
        }

        $key = mb_strtolower(trim($statusName));
        if (array_key_exists($key, $this->typeCache)) {
            return $this->typeCache[$key];
        }

        $candidates = array_merge([$statusName], $this->typeSynonyms[$statusName] ?? []);
        foreach ($candidates as $candidate) {
            $id = TransactionType::where('name', $candidate)->value('id');
            if ($id) {
                return $this->typeCache[$key] = (int) $id;
            }
        }

        if ($allowFallback) {
            $fallback = TransactionType::query()->orderBy('id')->value('id');
            if ($fallback) {
                return $this->typeCache[$key] = (int) $fallback;
            }
        }

        return $this->typeCache[$key] = null;
    }

    private function resolveDirection(int $typeId): ?string
    {
        if (array_key_exists($typeId, $this->directionCache)) {
            return $this->directionCache[$typeId];
        }

        $typeName = $this->getTransactionTypeName($typeId);
        return $this->directionCache[$typeId] = TransactionDirection::directionFromTypeName($typeName);
    }

    private function getTransactionTypeName(int $typeId): ?string
    {
        if (!array_key_exists($typeId, $this->typeNameCache)) {
            $this->typeNameCache[$typeId] = TransactionType::whereKey($typeId)->value('name');
        }

        return $this->typeNameCache[$typeId];
    }

    private function defaultTransactionNote(Contract $contract, string $statusName): string
    {
        return "عملية {$statusName} للعقد رقم {$contract->contract_number}";
    }

    private function defaultLedgerNote(Contract $contract, string $statusName, int $investorId): string
    {
        return "قيد {$statusName} للعقد #{$contract->contract_number} (مستثمر #{$investorId})";
    }

    /** @param CarbonInterface|DateTimeInterface|string|null $value */
    private function normalizeDateTime($value): CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value->copy();
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_string($value) && trim($value) !== '') {
            return Carbon::parse($value);
        }

        return Carbon::now();
    }
}
