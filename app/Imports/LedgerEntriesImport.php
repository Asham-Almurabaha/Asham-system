<?php

namespace App\Imports;

use App\Models\LedgerEntry;
use App\Models\TransactionStatus;
use App\Models\TransactionType;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class LedgerEntriesImport implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    WithChunkReading,
    WithBatchInserts,
    SkipsOnFailure,
    SkipsOnError
{
    use Importable, SkipsFailures, SkipsErrors;

    protected int $rowCount      = 0;
    protected int $insertedCount = 0;
    protected int $skippedCount  = 0;

    public function model(array $row)
    {
        $this->rowCount++;

        try {
            $cat   = strtolower(trim((string) Arr::get($row, 'party_category', '')));
            $invId = Arr::get($row, 'investor_id');
            $stId  = (int) Arr::get($row, 'status_id');
            $bank  = Arr::get($row, 'bank_account_id');
            $safe  = Arr::get($row, 'safe_id');
            $amt   = (float) Arr::get($row, 'amount', 0);
            $date  = $this->parseDateToString(Arr::get($row, 'transaction_date'));
            $ref   = Arr::get($row, 'ref');
            $notes = Arr::get($row, 'notes');

            $contractId   = Arr::get($row, 'contract_id');
            $installmentId= Arr::get($row, 'installment_id');

            // ===== تحققات منطقية إضافية =====
            if (!in_array($cat, ['investors','office'], true)) {
                throw new \RuntimeException('party_category يجب أن تكون investors أو office.');
            }
            if ($cat === 'investors' && empty($invId)) {
                throw new \RuntimeException('investor_id مطلوب عند اختيار party_category=investors.');
            }
            if ((empty($bank) && empty($safe)) || (!empty($bank) && !empty($safe))) {
                throw new \RuntimeException('اختر حسابًا واحدًا فقط: bank_account_id أو safe_id.');
            }
            if (!$date) {
                throw new \RuntimeException('transaction_date غير صالح.');
            }
            if ($amt <= 0) {
                throw new \RuntimeException('amount يجب أن يكون أكبر من صفر.');
            }

            $status = TransactionStatus::find($stId);
            if (!$status) {
                throw new \RuntimeException('status_id غير موجود.');
            }

            $typeId = (int) ($status->transaction_type_id ?? 0);
            if ($typeId <= 0) {
                throw new \RuntimeException('لا يوجد transaction_type مرتبط بهذه الحالة.');
            }

            $typeName = TransactionType::whereKey($typeId)->value('name') ?? null;
            $direction = $this->directionFromTypeName($typeName);
            if (!in_array($direction, ['in','out'], true)) {
                throw new \RuntimeException('تعذّر استنتاج الاتجاه (direction) من نوع الحركة.');
            }

            $entry = new LedgerEntry([
                'entry_date'            => $date,
                'investor_id'           => $cat === 'investors' ? $invId : null,
                'is_office'             => $cat === 'office',
                'transaction_status_id' => $stId,
                'transaction_type_id'   => $typeId,
                'bank_account_id'       => $bank ?: null,
                'safe_id'               => $safe ?: null,
                'contract_id'           => $contractId ?: null,
                'installment_id'        => $installmentId ?: null,
                'amount'                => $amt,
                'direction'             => $direction,
                'ref'                   => $ref,
                'notes'                 => $notes,
            ]);

            $entry->save();
            $this->insertedCount++;

            return $entry;

        } catch (\Throwable $e) {
            // يتم تسجيل الخطأ عبر SkipsOnError + زياده عداد المتخطّي
            $this->skippedCount++;
            throw $e;
        }
    }

    public function rules(): array
    {
        // فاليديشن أساسي؛ التحققات المركبة نتعامل معها داخل model()
        return [
            '*.party_category'   => ['required','in:investors,office'],
            '*.investor_id'      => ['nullable','integer','exists:investors,id'],
            '*.status_id'        => ['required','integer','exists:transaction_statuses,id'],
            '*.bank_account_id'  => ['nullable','integer','exists:bank_accounts,id'],
            '*.safe_id'          => ['nullable','integer','exists:safes,id'],
            '*.amount'           => ['required','numeric','min:0.01'],
            '*.transaction_date' => ['required'],
            '*.contract_id'      => ['nullable','integer','exists:contracts,id'],
            '*.installment_id'   => ['nullable','integer','exists:contract_installments,id'],
            '*.ref'              => ['nullable','string','max:255'],
            '*.notes'            => ['nullable','string'],
        ];
    }

    public function customValidationAttributes()
    {
        return [
            'party_category'   => 'الفئة',
            'investor_id'      => 'المستثمر',
            'status_id'        => 'الحالة',
            'bank_account_id'  => 'الحساب البنكي',
            'safe_id'          => 'الخزنة',
            'amount'           => 'المبلغ',
            'transaction_date' => 'تاريخ العملية',
            'contract_id'      => 'العقد',
            'installment_id'   => 'القسط',
            'ref'              => 'المرجع',
            'notes'            => 'ملاحظات',
        ];
    }

    public function batchSize(): int { return 500; }
    public function chunkSize(): int { return 500; }

    public function getRowCount(): int      { return $this->rowCount; }
    public function getInsertedCount(): int { return $this->insertedCount; }
    public function getSkippedCount(): int  { return $this->skippedCount; }

    /** إرجاع Y-m-d من نص/سيريال إكسل */
    protected function parseDateToString($value): ?string
    {
        if ($value === null || $value === '') return null;

        if (is_numeric($value) && (float)$value > 10000) {
            try {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value);
                return Carbon::instance($dt)->format('Y-m-d');
            } catch (\Throwable $e) {}
        }

        try { return Carbon::parse($value)->format('Y-m-d'); }
        catch (\Throwable $e) { return null; }
    }

    protected function arNormalize(?string $text): string
    {
        if ($text === null) return '';
        $text = trim(mb_strtolower($text, 'UTF-8'));
        return strtr($text, [
            'أ'=>'ا','إ'=>'ا','آ'=>'ا','ة'=>'ه','ى'=>'ي','ؤ'=>'و','ئ'=>'ي',
        ]);
    }

    protected function directionFromTypeName(?string $typeName): ?string
    {
        $name = $this->arNormalize($typeName);

        // مطابقات مباشرة
        $exact = [
            'ايداع'=>'in','إيداع'=>'in','توريد'=>'in','تحصيل'=>'in',
            'سحب'=>'out','صرف'=>'out','توزيع'=>'out','استرداد'=>'out',
            'deposit'=>'in','withdraw'=>'out',
        ];
        if (isset($exact[$typeName])) return $exact[$typeName];

        if (str_contains($name, 'ايداع') || str_contains($name, 'توريد') || str_contains($name, 'تحصيل')) return 'in';
        if (str_contains($name, 'سحب')  || str_contains($name, 'صرف')  || str_contains($name, 'توزيع')  || str_contains($name, 'استرداد')) return 'out';
        if (str_contains($name, 'deposit'))  return 'in';
        if (str_contains($name, 'withdraw')) return 'out';

        return null;
    }
}
