<?php

namespace Modules\Guarantors\Imports;

use Modules\Guarantors\Entities\Guarantor;
use Modules\Lookups\Entities\GuarantorStatus;
use Modules\Lookups\Entities\Nationality;
use Modules\Lookups\Entities\Title;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Validators\Failure;

class GuarantorsImport implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    SkipsOnFailure,
    SkipsOnError,
    WithChunkReading,
    WithBatchInserts
{
    use Importable;
    use \Maatwebsite\Excel\Concerns\SkipsFailures { onFailure as traitOnFailure; }
    use SkipsErrors;

    protected int $rows      = 0;
    protected int $inserted  = 0;
    protected int $updated   = 0;   // تكملة الناقص فقط
    protected int $unchanged = 0;
    protected int $skipped   = 0;
    protected int $failedByValidation = 0;

    protected int $pending   = 0;

    protected array $skippedSimple  = [];
    protected array $pendingUpdates = [];

    public function headingRow(): int { return 1; }

    public function onFailure(Failure ...$failures): void
    {
        $this->failedByValidation += count($failures);
        $this->traitOnFailure(...$failures);

        foreach ($failures as $f) {
            $this->skipped++;
            $this->skippedSimple[] = [
                'row'    => (int)$f->row(),
                'values' => (array)$f->values(),
                'reason' => implode(' | ', (array)$f->errors()),
            ];
        }
    }

    public function model(array $row)
    {
        $this->rows++;

        $name       = $this->safeStr($row['name'] ?? $row['الاسم'] ?? null);
        $nationalId = $this->digitsOnly($row['national_id'] ?? $row['الهوية'] ?? null);
        $phoneRaw   = $row['phone'] ?? $row['الجوال'] ?? null;
        $phone      = $this->normalizeSaudiPhone($phoneRaw);
        $email      = $this->safeStr($row['email'] ?? null);
        $address    = $this->safeStr($row['address'] ?? $row['العنوان'] ?? null);
        $notes      = $this->safeStr($row['notes'] ?? $row['ملاحظات'] ?? null);

        $idCardImage   = $this->safeStr($row['id_card_image']   ?? $row['صورة_الهوية'] ?? null);
        $contractImage = $this->safeStr($row['contract_image']  ?? $row['صورة_العقد']  ?? null);

        $nationalityId     = $row['nationality_id']     ?? $row['الجنسية_id']     ?? null;
        $titleId           = $row['title_id']           ?? $row['الوظيفة_id']     ?? null;
        $guarantorStatusId = $row['guarantor_status_id'] ?? $row['حالة_الكفيل_id'] ?? null;
        $nationalityName   = $row['nationality']        ?? $row['الجنسية']       ?? null;
        $titleName         = $row['title']              ?? $row['الوظيفة']        ?? null;
        $guarantorStatusName = $row['guarantor_status'] ?? $row['حالة_الكفيل']   ?? null;

        if (!$nationalityId && $nationalityName) {
            $nationalityId = $this->resolveIdByName($nationalityName, Nationality::class, ['name','name_en']);
        }
        if (!$titleId && $titleName) {
            $titleId = $this->resolveIdByName($titleName, Title::class, ['name','name_en']);
        }
        if (!$guarantorStatusId && $guarantorStatusName) {
            $guarantorStatusId = $this->resolveIdByName($guarantorStatusName, GuarantorStatus::class, ['name']);
        }

        // تعريف أدنى: name + (national_id أو phone)
        if (!$name || (!$nationalId && !$phone)) {
            $this->skipped++;
            $this->skippedSimple[] = [
                'row'    => $this->rows,
                'values' => $row,
                'reason' => 'Missing identifier (name + national_id/phone)',
            ];
            return null;
        }

        $found = Guarantor::where('national_id', $nationalId)->first()
            ?: Guarantor::where('phone', $phone)->first()
            ?: Guarantor::where('name', $name)->first();

        $payload = [
            'name'           => $name,
            'national_id'    => $nationalId,
            'phone'          => $phone,
            'email'          => $email ?: null,
            'address'        => $address ?: null,
            'notes'          => $notes ?: null,
            'nationality_id' => $nationalityId ?: null,
            'title_id'       => $titleId ?: null,
            'guarantor_status_id' => $guarantorStatusId ?: null,
            'id_card_image'  => $idCardImage ?: null,
            'contract_image' => $contractImage ?: null,
        ];

        $updates = $payload;
        foreach ($updates as $k => $v) {
            if (is_null($v)) unset($updates[$k]);
        }

        try {
            if ($found) {
                $diff = [];
                foreach ($updates as $field => $value) {
                    $current = $found->getAttribute($field);
                    if ($this->valuesEqual($current, $value)) {
                        unset($updates[$field]);
                        continue;
                    }

                    $diff[$field] = [
                        'old' => $current,
                        'new' => $value,
                    ];
                }

                if (empty($diff)) {
                    $this->unchanged++;
                    return null;
                }

                $token = (string) Str::uuid();

                $this->pending++;
                $this->pendingUpdates[$token] = [
                    'token' => $token,
                    'row'   => $this->rows,
                    'guarantor_id'   => $found->id,
                    'guarantor_name' => $found->name,
                    'identifiers'    => [
                        'national_id' => $found->national_id,
                        'phone'       => $found->phone,
                    ],
                    'diff'    => $diff,
                    'updates' => $updates,
                    'payload' => $payload,
                ];
            } else {
                Guarantor::create($payload);
                $this->inserted++;
            }
        } catch (\Throwable $e) {
            $this->skipped++;
            $this->skippedSimple[] = [
                'row'    => $this->rows,
                'values' => $row,
                'reason' => 'Save error: '.$e->getMessage(),
            ];
            $this->onError($e);
        }

        return null;
    }

    public function rules(): array
    {
        return [
            '*.name'        => 'required|string|max:255',
            // مشدّد (اختياري):
            // '*.national_id' => ['required','digits:10','regex:/^[12]\d{9}$/'],
            // '*.phone'       => ['required','regex:/^(?:05\d{8}|\+?9665\d{8}|009665\d{8}|9665\d{8})$/'],

            '*.national_id' => 'nullable',
            '*.phone'       => 'nullable',

            '*.email'       => 'nullable|email|max:255',
            '*.address'     => 'nullable|string',
            '*.notes'       => 'nullable|string',
            '*.nationality_id' => 'nullable|integer|exists:nationalities,id',
            '*.nationality' => 'nullable|string|max:255',
            '*.title_id'    => 'nullable|integer|exists:titles,id',
            '*.title'       => 'nullable|string|max:255',
            '*.guarantor_status_id' => 'nullable|integer|exists:guarantor_statuses,id',
            '*.guarantor_status'    => 'nullable|string|max:255',
            '*.id_card_image'  => 'nullable|string|max:255',
            '*.contract_image' => 'nullable|string|max:255',
        ];
    }

    public function customValidationMessages()
    {
        return [
            '*.name.required'        => 'الاسم مطلوب.',
            '*.national_id.required' => 'رقم الهوية مطلوب.',
            '*.national_id.digits'   => 'رقم الهوية يجب أن يكون 10 أرقام.',
            '*.national_id.regex'    => 'رقم الهوية يجب أن يبدأ بـ 1 أو 2.',
            '*.phone.required'       => 'رقم الجوال مطلوب.',
            '*.phone.regex'          => 'صيغة رقم الجوال غير صحيحة.',
            '*.email.email'          => 'البريد الإلكتروني غير صالح.',
        ];
    }

    public function chunkSize(): int { return 1000; }
    public function batchSize(): int { return 1000; }

    // Getters
    public function getRowCount(): int       { return $this->rows; }
    public function getInsertedCount(): int  { return $this->inserted; }
    public function getUpdatedCount(): int   { return $this->updated; }
    public function getUnchangedCount(): int { return $this->unchanged; }
    public function getSkippedCount(): int   { return $this->skipped; }
    public function getFailedValidationCount(): int { return $this->failedByValidation; }

    public function getPendingCount(): int   { return $this->pending; }

    public function getPendingUpdates(): array
    {
        return $this->pendingUpdates;
    }

    public function skipped(): array
    {
        return $this->skippedSimple;
    }

    // Helpers
    private function safeStr($v): ?string
    {
        if ($v === null) return null;
        $v = trim((string)$v);
        return $v !== '' ? $v : null;
    }
    private function digitsOnly(?string $v): ?string
    {
        if (!$v) return null;
        return preg_replace('/\D+/', '', (string)$v);
    }
    private function normalizeSaudiPhone(?string $phone): ?string
    {
        if (!$phone) return null;
        $d = preg_replace('/\D+/', '', (string)$phone);
        if (preg_match('/^009665(\d{8})$/', $d, $m)) return '9665'.$m[1];
        if (preg_match('/^9665(\d{8})$/',   $d, $m)) return '9665'.$m[1];
        if (preg_match('/^05(\d{8})$/',     $d, $m)) return '9665'.$m[1];
        if (preg_match('/^\+9665(\d{8})$/', (string)$phone, $m)) return '9665'.$m[1];
        return $d;
    }
    private function resolveIdByName(?string $name, string $modelClass, array $columns = ['name']): ?int
    {
        $name = $this->safeStr($name);
        if (!$name) return null;

        $instance = app($modelClass);
        $table = $instance->getTable();

        $available = [];
        foreach ($columns as $col) if (Schema::hasColumn($table, $col)) $available[] = $col;
        if (empty($available)) { if (Schema::hasColumn($table, 'name')) $available = ['name']; else return null; }

        $q = $modelClass::query();
        $q->where(function($qq) use ($name, $available) {
            foreach ($available as $col) $qq->orWhere($col, $name);
        });
        $found = $q->first();
        if ($found) return (int)$found->id;

        $needle = Str::of($name)->lower()->squish()->value();
        $found = $modelClass::get()->first(function($row) use ($available, $needle) {
            foreach ($available as $col) {
                $val = Str::of((string)($row->{$col} ?? ''))->lower()->squish()->value();
                if ($val === $needle) return true;
            }
            return false;
        });
        return $found ? (int)$found->id : null;
    }

    private function valuesEqual($a, $b): bool
    {
        if ($a === null && $b === null) return true;
        if ($a === null || $b === null) return false;

        if (is_string($a)) $a = trim($a);
        if (is_string($b)) $b = trim($b);

        return $a == $b;
    }
}
