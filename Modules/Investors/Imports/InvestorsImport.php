<?php

namespace Modules\Investors\Imports;

use Modules\Lookups\Entities\Nationality;
use Modules\Lookups\Entities\Title;
use Modules\Investors\Entities\Investor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Lang;
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

class InvestorsImport implements
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
    protected int $updated   = 0;   // تغييرات فعلية فقط (بعد التأكيد)
    protected int $unchanged = 0;   // لم تتغيّر
    protected int $skipped   = 0;   // تخطّي داخل model()
    protected int $failedByValidation = 0; // فشل قبل model()

    protected int $pending   = 0;   // تعديلات بانتظار التأكيد

    /** المتخطّي بصيغة مبسطة للتصدير: كل عنصر ['row'=>int,'values'=>array,'reason'=>string] */
    protected array $skippedSimple = [];
    protected array $pendingUpdates = [];

    /** @var array<string, array<int, string>>|null */
    protected ?array $headingAliasMap = null;

    /** @var array<string, array<int, string>> */
    protected array $headingManualAliases = [
        'name' => ['الاسم'],
        'national_id' => ['رقم الهوية', 'رقم الهوية الوطنية', 'الهوية الوطنية', 'الهوية'],
        'phone' => ['الجوال', 'رقم الجوال', 'الهاتف', 'رقم الهاتف'],
        'email' => ['البريد الإلكتروني', 'البريد الالكتروني', 'الايميل'],
        'address' => ['العنوان'],
        'nationality_id' => ['الجنسية_id', 'معرّف الجنسية', 'معرف الجنسية'],
        'nationality' => ['الجنسية', 'جنسية'],
        'title_id' => ['الوظيفة_id', 'معرّف الوظيفة', 'معرف الوظيفة'],
        'title' => ['الوظيفة', 'المسمى الوظيفي'],
        'id_card_image' => ['صورة الهوية', 'صورة_الهوية'],
        'contract_image' => ['صورة العقد', 'صورة_العقد'],
        'office_share_percentage' => ['نسبة مشاركة المكتب', 'نسبة_مشاركة_المكتب', 'نسبة المكتب'],
        'investment_start_date' => ['تاريخ بدء الاستثمار', 'تاريخ_بدء_الاستثمار', 'start date', 'investment start', 'investment_start'],
    ];

    public function headingRow(): int { return 1; }

    /**
     * إخفاقات التحقق قبل دخول model():
     * - نمررها للـ trait لتظل متاحة عبر failures()
     * - نزيد عداد failedByValidation
     * - نضيفها لقائمة skippedSimple كصفوف متخطّاة مع السبب
     */
    public function onFailure(Failure ...$failures): void
    {
        $this->failedByValidation += count($failures);

        // احتفظ بنسخة كاملة عبر الـ trait
        $this->traitOnFailure(...$failures);

        // وأضف نسخة مبسطة للمتخطّي
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

        $row = $this->normalizeRowKeys($row);

        // خرائط عربي/إنجليزي
        $name       = $this->safeStr($row['name'] ?? $row['الاسم'] ?? null);
        $nationalId = $this->digitsOnly($row['national_id'] ?? $row['الهوية'] ?? null);
        $phoneRaw   = $row['phone'] ?? $row['الجوال'] ?? null;
        $phone      = $this->normalizeSaudiPhone($phoneRaw);
        $email      = $this->safeStr($row['email'] ?? null);
        $address    = $this->safeStr($row['address'] ?? $row['العنوان'] ?? null);

        $idCardImage   = $this->safeStr($row['id_card_image']   ?? $row['صورة_الهوية'] ?? null);
        $contractImage = $this->safeStr($row['contract_image']  ?? $row['صورة_العقد']  ?? null);
        $investmentStart = $this->parseDate($row['investment_start_date']
            ?? $row['start_date']
            ?? $row['investment_start']
            ?? $row['تاريخ_بدء_الاستثمار']
            ?? null);

        $shareRaw   = $row['office_share_percentage'] ?? $row['نسبة_مشاركة_المكتب'] ?? null;
        $officeShare = is_null($shareRaw) ? null : (float)str_replace(['%',' '], '', (string)$shareRaw);

        // IDs مباشرة أو بالأسماء
        $nationalityId   = $row['nationality_id'] ?? $row['الجنسية_id'] ?? null;
        $titleId         = $row['title_id']       ?? $row['الوظيفة_id']   ?? null;
        $nationalityName = $row['nationality']    ?? $row['الجنسية']     ?? null;
        $titleName       = $row['title']          ?? $row['الوظيفة']      ?? null;

        if (!$nationalityId && $nationalityName) {
            $nationalityId = $this->resolveIdByName($nationalityName, Nationality::class, ['name','name_en']);
        }
        if (!$titleId && $titleName) {
            $titleId = $this->resolveIdByName($titleName, Title::class, ['name','name_en']);
        }

        // إلزاميات دنيا لتعريف السجل — (التصديق النهائي على الإلزاميات يتم في rules())
        if (!$name || (!$nationalId && !$phone)) {
            $this->skipped++;
            $this->skippedSimple[] = [
                'row'    => $this->rows,         // رقم الصف التقريبي أثناء المعالجة
                'values' => $row,
                'reason' => 'Missing identifier (name + national_id/phone)',
            ];
            return null;
        }

        // تعريف السجل
        $found = Investor::where('national_id', $nationalId)->first()
            ?: Investor::where('phone', $phone)->first()
            ?: Investor::where('name', $name)->first();

        $payload = [
            'name'                    => $name,
            'national_id'             => $nationalId,
            'phone'                   => $phone,
            'email'                   => $email ?: null,
            'address'                 => $address ?: null,
            'nationality_id'          => $nationalityId ?: null,
            'title_id'                => $titleId ?: null,
            'id_card_image'           => $idCardImage ?: null,
            'contract_image'          => $contractImage ?: null,
            'office_share_percentage' => $officeShare,
            'investment_start_date'   => $investmentStart,
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
                    'investor_id'   => $found->id,
                    'investor_name' => $found->name,
                    'identifiers'   => [
                        'national_id' => $found->national_id,
                        'phone'       => $found->phone,
                    ],
                    'diff'    => $diff,
                    'updates' => $updates,
                    'payload' => $payload,
                ];
            } else {
                Investor::create($payload);
                $this->inserted++;
            }
        } catch (\Throwable $e) {
            // اعتبره متخطّي بسبب خطأ أثناء الحفظ
            $this->skipped++;
            $this->skippedSimple[] = [
                'row'    => $this->rows,
                'values' => $row,
                'reason' => 'Save error: '.$e->getMessage(),
            ];
            // مرّر للـ trait ليجمع errors() لو حبيت تستخدمها
            $this->onError($e);
        }

        return null; // منع الحفظ المزدوج
    }

    /** ✅ رجّعنا الفاليديشن الصارم للهوية والجوال */
    public function rules(): array
    {
        return [
            '*.name'                    => 'required|string|max:255',
            // '*.national_id'             => ['required','digits:10','regex:/^[12]\d{9}$/'],
            // '*.phone'                   => ['required','regex:/^(?:05\d{8}|\+?9665\d{8}|009665\d{8}|9665\d{8})$/'],

            '*.national_id'             => 'nullable',
            '*.phone'                   => 'nullable',

            '*.email'                   => 'nullable|email|max:255',
            '*.address'                 => 'nullable|string',
            '*.nationality_id'          => 'nullable|integer|exists:nationalities,id',
            '*.nationality'             => 'nullable|string|max:255',
            '*.title_id'                => 'nullable|integer|exists:titles,id',
            '*.title'                   => 'nullable|string|max:255',
            '*.id_card_image'           => 'nullable|string|max:255',
            '*.contract_image'          => 'nullable|string|max:255',
            '*.office_share_percentage' => 'nullable|numeric|min:0|max:100',
            '*.investment_start_date'   => 'nullable|date',
        ];
    }

    public function prepareForValidation(array $data, int $index)
    {
        $row = $this->normalizeRowKeys($data);

        if (array_key_exists('investment_start_date', $row)) {
            $row['investment_start_date'] = $this->normalizeDateForValidation($row['investment_start_date']);
        }

        return $row;
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
            '*.office_share_percentage.numeric' => 'نسبة مشاركة المكتب يجب أن تكون رقمية.',
            '*.office_share_percentage.min'     => 'النسبة لا تقل عن 0%.',
            '*.office_share_percentage.max'     => 'النسبة لا تزيد عن 100%.',
            '*.investment_start_date.date'      => 'تاريخ بدء الاستثمار غير صالح.',
        ];
    }

    public function chunkSize(): int { return 1000; }
    public function batchSize(): int { return 1000; }

    // ===== Getters للأرقام والنتائج =====
    public function getRowCount(): int       { return $this->rows; }
    public function getInsertedCount(): int  { return $this->inserted; }
    public function getUpdatedCount(): int   { return $this->updated; }
    public function getUnchangedCount(): int { return $this->unchanged; }
    public function getSkippedCount(): int   { return $this->skipped; }
    public function getFailedValidationCount(): int { return $this->failedByValidation; }

    public function getPendingCount(): int
    {
        return $this->pending;
    }

    public function getPendingUpdates(): array
    {
        return $this->pendingUpdates;
    }

    /** المتخطّي بصيغته المبسّطة للتصدير (failures + skips داخل model) */
    public function skipped(): array
    {
        return $this->skippedSimple;
    }

    // ===== Helpers =====
    private function normalizeRowKeys(array $row): array
    {
        foreach ($this->headingAliasMap() as $canonical => $aliases) {
            $hasCanonical = array_key_exists($canonical, $row) && $this->valueIsFilled($row[$canonical]);

            if ($hasCanonical) {
                continue;
            }

            foreach ($aliases as $alias) {
                if ($alias === $canonical) {
                    continue;
                }

                if (array_key_exists($alias, $row) && $this->valueIsFilled($row[$alias])) {
                    $row[$canonical] = $row[$alias];
                    break;
                }
            }
        }

        return $row;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function headingAliasMap(): array
    {
        if ($this->headingAliasMap !== null) {
            return $this->headingAliasMap;
        }

        $map = [];

        foreach ($this->canonicalHeadingKeys() as $key) {
            $aliases = [$key];
            $aliases = array_merge($aliases, $this->headingManualAliases[$key] ?? []);

            foreach ($this->headingTranslations($key) as $translation) {
                $aliases[] = $translation;
                $aliases = array_merge($aliases, $this->slugVariants($translation));
            }

            $aliases = array_merge($aliases, $this->slugVariants($key));

            $map[$key] = $this->uniqueHeadingAliases($aliases);
        }

        return $this->headingAliasMap = $map;
    }

    /**
     * @return array<int, string>
     */
    private function canonicalHeadingKeys(): array
    {
        return [
            'name',
            'national_id',
            'phone',
            'email',
            'address',
            'nationality_id',
            'nationality',
            'title_id',
            'title',
            'id_card_image',
            'contract_image',
            'office_share_percentage',
            'investment_start_date',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function headingTranslations(string $key): array
    {
        $locales = array_unique(array_filter([
            app()->getLocale(),
            config('app.fallback_locale'),
            'ar',
            'en',
        ]));

        $translations = [];
        $fullKey = 'export.headings.' . $key;

        foreach ($locales as $locale) {
            if (Lang::has($fullKey, $locale)) {
                $translations[] = Lang::get($fullKey, [], $locale);
            }
        }

        return array_values(array_unique(array_filter($translations, static function ($value) {
            return is_string($value) && trim($value) !== '';
        })));
    }

    /**
     * @return array<int, string>
     */
    private function slugVariants(string $value): array
    {
        $variants = [];

        foreach (['-', '_'] as $separator) {
            $slug = Str::slug($value, $separator);

            if ($slug !== '') {
                $variants[] = $slug;
                $variants[] = str_replace('-', '_', $slug);
                $variants[] = str_replace(['-', '_'], '', $slug);
            }
        }

        return array_values(array_unique(array_filter($variants)));
    }

    /**
     * @param  array<int, string>  $aliases
     * @return array<int, string>
     */
    private function uniqueHeadingAliases(array $aliases): array
    {
        $clean = [];

        foreach ($aliases as $alias) {
            if (!is_string($alias)) {
                continue;
            }

            $trimmed = trim($alias);

            if ($trimmed === '') {
                continue;
            }

            $clean[] = $trimmed;
            $clean[] = mb_strtolower($trimmed, 'UTF-8');
        }

        return array_values(array_unique($clean));
    }

    private function valueIsFilled($value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return !empty($value);
        }

        return true;
    }

    private function normalizeDateForValidation($value)
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface || is_numeric($value)) {
            return $this->parseDate($value) ?? $value;
        }

        if (is_string($value)) {
            $normalized = $this->stripDirectionalFormatting(
                $this->normalizeLocalizedDigits($value)
            );

            $normalized = preg_replace('/\s+/u', ' ', $normalized ?? '');
            $normalized = trim((string) $normalized);

            if ($normalized === '') {
                return null;
            }

            return $this->parseDate($normalized) ?? $normalized;
        }

        return $value;
    }

    private function normalizeLocalizedDigits(string $value): string
    {
        return strtr($value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
    }

    private function stripDirectionalFormatting(string $value): string
    {
        return str_replace([
            "\u{200f}", "\u{200e}", "\u{202a}", "\u{202b}", "\u{202c}",
            "\u{202d}", "\u{202e}", "\u{2066}", "\u{2067}", "\u{2068}",
            "\u{2069}",
        ], '', $value);
    }

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

        if ($a instanceof \Carbon\CarbonInterface) {
            $a = $a->toDateString();
        }
        if ($b instanceof \Carbon\CarbonInterface) {
            $b = $b->toDateString();
        }

        if (is_string($a)) $a = trim($a);
        if (is_string($b)) $b = trim($b);

        return $a == $b;
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }

        if (is_numeric($value) && class_exists(\PhpOffice\PhpSpreadsheet\Shared\Date::class)) {
            try {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value);
                return Carbon::instance($dt)->toDateString();
            } catch (\Throwable $e) {
                // ignore and fallback
            }
        }

        $stringValue = trim((string)$value);
        if ($stringValue === '') {
            return null;
        }

        try {
            return Carbon::parse($stringValue)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
