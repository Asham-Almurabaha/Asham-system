<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\Nationality;
use App\Models\Title;
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

class CustomersImport implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    SkipsOnFailure,
    SkipsOnError,
    WithChunkReading,
    WithBatchInserts
{
    use Importable;
    // نحتاج alias علشان نزود عدّادنا ونحتفظ بسلوك SkipsFailures
    use \Maatwebsite\Excel\Concerns\SkipsFailures { onFailure as traitOnFailure; }
    use SkipsErrors;

    protected int $rows      = 0;   // اللي وصلت لـ model()
    protected int $inserted  = 0;
    protected int $updated   = 0;   // تحديثات حقيقية فقط (Dirty)
    protected int $unchanged = 0;   // اتلاقت بس بدون أي تغيير
    protected int $skipped   = 0;   // تخطّي داخل model()

    // صفوف فشلت في الـ validation قبل ما توصل model()
    protected int $failedByValidation = 0;

    public function headingRow(): int { return 1; }

    // نعد صفوف الـ validation failures ونخزنها داخليًا
    public function onFailure(Failure ...$failures): void
    {
        $this->failedByValidation += count($failures);
        $this->traitOnFailure(...$failures);
    }

    public function model(array $row)
    {
        $this->rows++;

        // رؤوس عربي/إنجليزي
        $name        = $this->safeStr($row['name'] ?? $row['الاسم'] ?? null);
        $nationalId  = $this->digitsOnly($row['national_id'] ?? $row['الهوية'] ?? null);
        $phoneRaw    = $row['phone'] ?? $row['الجوال'] ?? null;
        $phone       = $this->normalizeSaudiPhone($phoneRaw);
        $email       = $this->safeStr($row['email'] ?? null);
        $address     = $this->safeStr($row['address'] ?? $row['العنوان'] ?? null);
        $notes       = $this->safeStr($row['notes'] ?? $row['ملاحظات'] ?? null);

        // IDs مباشرة
        $nationalityId = $row['nationality_id'] ?? $row['الجنسية_id'] ?? null;
        $titleId       = $row['title_id']       ?? $row['الوظيفة_id']   ?? null;

        // أسماء تتحوّل لـ IDs
        $nationalityName = $row['nationality'] ?? $row['الجنسية'] ?? null;
        $titleName       = $row['title']       ?? $row['الوظيفة']  ?? null;

        if (!$nationalityId && $nationalityName) {
            $nationalityId = $this->resolveIdByName($nationalityName, Nationality::class, ['name','name_en']);
        }
        if (!$titleId && $titleName) {
            $titleId = $this->resolveIdByName($titleName, Title::class, ['name','name_en']);
        }

        $idCardImage = $this->safeStr($row['id_card_image'] ?? null);

        // إلزاميات دنيا
        if (!$name || !$nationalId || !$phone) {
            $this->skipped++;
            return null;
        }

        // مفتاح تعرّف للتحديث (أولوية: national_id ثم phone ثم name)
        $found = Customer::where('national_id', $nationalId)->first()
            ?: Customer::where('phone', $phone)->first()
            ?: Customer::where('name', $name)->first();

        $payload = [
            'name'           => $name,
            'national_id'    => $nationalId,
            'phone'          => $phone,
            'email'          => $email ?: null,
            'address'        => $address ?: null,
            'notes'          => $notes ?: null,
            'nationality_id' => $nationalityId ?: null,
            'title_id'       => $titleId ?: null,
            'id_card_image'  => $idCardImage ?: null,
        ];

        try {
            if ($found) {
                $found->fill($payload);
                if ($found->isDirty()) {
                    $found->save();
                    $this->updated++;
                } else {
                    $this->unchanged++;
                }
            } else {
                Customer::create($payload);
                $this->inserted++;
            }
        } catch (\Throwable $e) {
            $this->skipped++;
            // لتجميع الخطأ في $this->errors()
            $this->onError($e);
        }

        // احنا حفظنا يدويًا؛ ما نرجعش موديل عشان ما يحصلش حفظ مزدوج
        return null;
    }

    public function rules(): array
    {
        return [
            '*.name'           => 'required|string|max:255',
            '*.national_id'    => ['required','digits:10','regex:/^[12]\d{9}$/'],
            '*.phone'          => ['required','regex:/^(?:05\d{8}|\+?9665\d{8}|009665\d{8}|9665\d{8})$/'],
            '*.email'          => 'nullable|email|max:255',
            '*.address'        => 'nullable|string',
            '*.notes'          => 'nullable|string',
            '*.nationality_id' => 'nullable|integer|exists:nationalities,id',
            '*.nationality'    => 'nullable|string|max:255',
            '*.title_id'       => 'nullable|integer|exists:titles,id',
            '*.title'          => 'nullable|string|max:255',
            '*.id_card_image'  => 'nullable|string|max:255',
        ];
    }

    public function customValidationMessages()
    {
        return [
            '*.name.required'        => 'الاسم مطلوب.',
            '*.national_id.required' => 'رقم الهوية مطلوب.',
            '*.national_id.digits'   => 'رقم الهوية يجب أن يكون 10 أرقام.',
            '*.national_id.regex'    => 'رقم الهوية يجب أن يبدأ بـ 1 (مواطن) أو 2 (مقيم).',
            '*.phone.required'       => 'رقم الجوال مطلوب.',
            '*.phone.regex'          => 'صيغة رقم الجوال غير صحيحة.',
            '*.email.email'          => 'البريد الإلكتروني غير صالح.',
            '*.nationality_id.exists'=> 'الجنسية غير موجودة.',
            '*.title_id.exists'      => 'المسمى الوظيفي غير موجود.',
        ];
    }

    public function chunkSize(): int { return 1000; }
    public function batchSize(): int { return 1000; }

    // ===== Counters/Getters =====
    public function getRowCount(): int       { return $this->rows; }
    public function getInsertedCount(): int  { return $this->inserted; }
    public function getUpdatedCount(): int   { return $this->updated; }
    public function getUnchangedCount(): int { return $this->unchanged; }
    public function getSkippedCount(): int   { return $this->skipped; }
    public function getFailedValidationCount(): int { return $this->failedByValidation; }

    // ===== Helpers =====
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

        return $d;
    }

    private function resolveIdByName(?string $name, string $modelClass, array $columns = ['name']): ?int
    {
        $name = $this->safeStr($name);
        if (!$name) return null;

        $instance = app($modelClass);
        $table = $instance->getTable();

        $available = [];
        foreach ($columns as $col) {
            if (Schema::hasColumn($table, $col)) $available[] = $col;
        }
        if (empty($available)) {
            if (Schema::hasColumn($table, 'name')) $available = ['name']; else return null;
        }

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
}
