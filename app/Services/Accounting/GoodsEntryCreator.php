<?php

namespace App\Services\Accounting;

use App\Models\LedgerEntry;
use App\Models\OfficeTransaction;
use App\Models\ProductTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Lookups\Entities\TransactionStatus;

class GoodsEntryCreator
{
    private const STATUS_NAME_PRIMARY = 'شراء بطاقات';
    private const STATUS_NAME_FALLBACK = 'شراء بضائع';

    private const TYPE_IN = 1;
    private const TYPE_OUT = 2;

    /**
     * إنشاء قيد شراء بضائع (حساب واحد).
     */
    public function createEntry(array $payload): LedgerEntry
    {
        $status = $this->resolveStatus();
        $typeId = (int) $status->transaction_type_id;
        $direction = $this->directionFromType($typeId);

        $amount = $this->normalizeAmount($payload['amount'] ?? 0);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'المبلغ يجب أن يكون أكبر من صفر.',
            ]);
        }

        $products = $this->normalizeProducts($payload['products'] ?? []);
        if ($products->isEmpty()) {
            throw ValidationException::withMessages([
                'products' => 'أضف نوع بضاعة واحد على الأقل.',
            ]);
        }

        $bankId = $payload['bank_account_id'] ?? null;
        $safeId = $payload['safe_id'] ?? null;
        if (!$bankId && !$safeId) {
            throw ValidationException::withMessages([
                'bank_account_id' => 'اختر حسابًا بنكيًا أو خزنة.',
            ]);
        }
        if ($bankId && $safeId) {
            throw ValidationException::withMessages([
                'bank_account_id' => 'لا يمكن اختيار بنك وخزنة في نفس القيد.',
                'safe_id'         => 'لا يمكن اختيار بنك وخزنة في نفس القيد.',
            ]);
        }

        return DB::transaction(function () use ($payload, $status, $typeId, $direction, $amount, $products, $bankId, $safeId) {
            $entry = LedgerEntry::create([
                'entry_date'            => $payload['transaction_date'],
                'investor_id'           => null,
                'is_office'             => true,
                'transaction_status_id' => $status->id,
                'transaction_type_id'   => $typeId,
                'bank_account_id'       => $bankId,
                'safe_id'               => $safeId,
                'amount'                => $amount,
                'direction'             => $direction,
                'notes'                 => $payload['notes'] ?? null,
            ]);

            $this->storeProducts($entry, $products);

            $transaction = OfficeTransaction::create([
                'investor_id'      => null,
                'status_id'        => $status->id,
                'amount'           => $amount,
                'transaction_date' => $payload['transaction_date'],
                'notes'            => $payload['notes'] ?? null,
            ]);

            $entry->update(['ref' => 'OT-' . $transaction->id]);

            return $entry->fresh(['productTransactions']);
        });
    }

    /**
     * إنشاء قيد شراء بضائع مُجزّأ (بنك + خزنة).
     *
     * @return \Illuminate\Support\Collection<int, LedgerEntry>
     */
    public function createPartial(array $payload): Collection
    {
        $status = $this->resolveStatus();
        $typeId = (int) $status->transaction_type_id;
        $direction = $this->directionFromType($typeId);

        $bankShare = $this->normalizeAmount($payload['bank_share'] ?? 0);
        $safeShare = $this->normalizeAmount($payload['safe_share'] ?? 0);
        $total = $this->normalizeAmount($payload['amount'] ?? 0);

        if ($total <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'إجمالي المبلغ يجب أن يكون أكبر من صفر.',
            ]);
        }

        if ($bankShare <= 0 && $safeShare <= 0) {
            throw ValidationException::withMessages([
                'bank_share' => 'أدخل قيمة في البنك أو الخزنة على الأقل.',
                'safe_share' => 'أدخل قيمة في البنك أو الخزنة على الأقل.',
            ]);
        }

        if ($this->round2($bankShare + $safeShare) !== $total) {
            throw ValidationException::withMessages([
                'amount' => 'يجب أن يساوي مجموع البنك + الخزنة إجمالي المبلغ.',
            ]);
        }

        if ($bankShare > 0 && empty($payload['bank_account_id'])) {
            throw ValidationException::withMessages([
                'bank_account_id' => 'اختر الحساب البنكي لهذا الجزء.',
            ]);
        }
        if ($safeShare > 0 && empty($payload['safe_id'])) {
            throw ValidationException::withMessages([
                'safe_id' => 'اختر الخزنة لهذا الجزء.',
            ]);
        }

        $products = $this->normalizeProducts($payload['products'] ?? []);
        if ($products->isEmpty()) {
            throw ValidationException::withMessages([
                'products' => 'أضف نوع بضاعة واحد على الأقل.',
            ]);
        }

        return DB::transaction(function () use ($payload, $status, $typeId, $direction, $bankShare, $safeShare, $total, $products) {
            $entries = collect();

            $bankEntry = null;
            if ($bankShare > 0) {
                $bankEntry = LedgerEntry::create([
                    'entry_date'            => $payload['transaction_date'],
                    'investor_id'           => null,
                    'is_office'             => true,
                    'transaction_status_id' => $status->id,
                    'transaction_type_id'   => $typeId,
                    'bank_account_id'       => $payload['bank_account_id'],
                    'safe_id'               => null,
                    'amount'                => $bankShare,
                    'direction'             => $direction,
                    'notes'                 => $payload['notes'] ?? null,
                ]);

                $entries->push($bankEntry);
            }

            $safeEntry = null;
            if ($safeShare > 0) {
                $safeEntry = LedgerEntry::create([
                    'entry_date'            => $payload['transaction_date'],
                    'investor_id'           => null,
                    'is_office'             => true,
                    'transaction_status_id' => $status->id,
                    'transaction_type_id'   => $typeId,
                    'bank_account_id'       => null,
                    'safe_id'               => $payload['safe_id'],
                    'amount'                => $safeShare,
                    'direction'             => $direction,
                    'notes'                 => $payload['notes'] ?? null,
                ]);

                $entries->push($safeEntry);
            }

            $anchor = $bankEntry ?? $safeEntry;
            if ($anchor) {
                $this->storeProducts($anchor, $products);
            }

            if ($entries->isNotEmpty()) {
                $transaction = OfficeTransaction::create([
                    'investor_id'      => null,
                    'status_id'        => $status->id,
                    'amount'           => $total,
                    'transaction_date' => $payload['transaction_date'],
                    'notes'            => $payload['notes'] ?? null,
                ]);

                LedgerEntry::whereIn('id', $entries->pluck('id'))->update([
                    'ref' => 'OT-' . $transaction->id,
                ]);
            }

            return $entries->filter()->values();
        });
    }

    private function resolveStatus(): TransactionStatus
    {
        $status = TransactionStatus::where('name', self::STATUS_NAME_PRIMARY)->first();
        if (!$status) {
            $status = TransactionStatus::where('name', self::STATUS_NAME_FALLBACK)->first();
        }

        if (!$status) {
            throw ValidationException::withMessages([
                'status_id' => 'تعذر تحديد حالة "شراء البضائع" المطلوبة.',
            ]);
        }

        return $status;
    }

    private function directionFromType(int $typeId): string
    {
        return $typeId === self::TYPE_IN ? 'in' : 'out';
    }

    private function normalizeAmount($value): float
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', $value);
        }

        return $this->round2((float) $value);
    }

    private function normalizeProducts($rows): Collection
    {
        return collect($rows)
            ->map(function ($row) {
                return [
                    'product_type_id' => isset($row['product_type_id']) ? (int) $row['product_type_id'] : 0,
                    'quantity'        => isset($row['quantity']) ? (int) $row['quantity'] : 0,
                ];
            })
            ->filter(fn ($row) => $row['product_type_id'] > 0 && $row['quantity'] > 0)
            ->values();
    }

    private function storeProducts(LedgerEntry $entry, Collection $products): void
    {
        foreach ($products as $row) {
            $payload = [
                'ledger_entry_id' => $entry->id,
                'quantity'        => $row['quantity'],
            ];

            if (Schema::hasColumn('product_transactions', 'product_type_id')) {
                $payload['product_type_id'] = $row['product_type_id'];
            } elseif (Schema::hasColumn('product_transactions', 'goods_type_id')) {
                $payload['goods_type_id'] = $row['product_type_id'];
            } else {
                $payload['product_id'] = $row['product_type_id'];
            }

            ProductTransaction::create($payload);
        }
    }

    private function round2(float $value): float
    {
        return round($value, 2);
    }
}
