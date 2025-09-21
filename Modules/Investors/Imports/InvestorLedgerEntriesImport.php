<?php

namespace Modules\Investors\Imports;

use App\Imports\Concerns\DetectsEmptyRows;
use App\Models\LedgerEntry;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;
use Modules\Accounts\Entities\BankAccount;
use Modules\Accounts\Entities\Safe;
use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Entities\ContractInstallment;
use Modules\Investors\Entities\Investor;
use Modules\Investors\Entities\InvestorTransaction;
use Modules\Lookups\Entities\TransactionStatus;
use Modules\Lookups\Entities\TransactionType;

class InvestorLedgerEntriesImport implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    WithChunkReading,
    SkipsOnFailure,
    SkipsOnError
{
    use Importable;
    use SkipsFailures { onFailure as traitOnFailure; }
    use SkipsErrors;
    use DetectsEmptyRows;

    protected int $rowCount = 0;
    protected int $insertedCount = 0;
    protected int $skippedCount = 0;

    /** @var array<int, int|null> */
    protected array $investorsById = [];

    /** @var array<string, int|null> */
    protected array $investorsByName = [];

    protected bool $investorsLoaded = false;

    /** @var array<int, TransactionStatus|null> */
    protected array $statusesById = [];

    /** @var array<string, TransactionStatus|null> */
    protected array $statusesByName = [];

    protected bool $statusesLoaded = false;

    /** أسماء حالات مستبعدة من استيراد المستثمرين */
    protected array $statusesDisallowedNames = ['فرق البيع', 'إضافة عقد', 'سداد قسط'];

    /** الفئة الخاصة بالمستثمرين في Pivot الحالات */
    protected int $categoryInvestorsId = 1;

    /**
     * @var array<string, array<int, string>>|null
     */
    protected ?array $headingAliasMap = null;

    /**
     * مفاتيح رأس الجدول الإضافية (slug) للملف العربي.
     * @var array<string, array<int, string>>
     */
    protected array $headingManualAliases = [
        'investor_id'      => ['almstthmr_maarf_ao_asm', 'almstthmr-marf-ao-asm'],
        'status_id'        => ['alhal_maarf_ao_asm', 'alhal-marf-ao-asm'],
        'bank_account_id'  => ['alhsab_albnky_maarf_ao_asm', 'alhsab-albnky-marf-ao-asm'],
        'safe_id'          => ['alkhzn_maarf_ao_asm', 'alkhzn-marf-ao-asm'],
        'amount'           => ['almblgh'],
        'transaction_date' => ['tarykh_alaamly', 'tarykh-alamly'],
        'contract_id'      => ['alaakd_maarf_ao_rkm', 'alaakd-marf-ao-rkm'],
        'installment_id'   => ['alkst_maarf_ao_rkm', 'alkst-marf-ao-rkm'],
        'ref'              => ['almrgaa', 'almrga', 'almrjaa'],
        'notes'            => ['mlahthat'],
    ];

    /** @var array<int, int|null> */
    protected array $bankAccountsById = [];

    /** @var array<string, int|null> */
    protected array $bankAccountsByName = [];

    protected bool $bankAccountsLoaded = false;

    /** @var array<int, int|null> */
    protected array $safesById = [];

    /** @var array<string, int|null> */
    protected array $safesByName = [];

    protected bool $safesLoaded = false;

    /** @var array<int, int|null> */
    protected array $contractsById = [];

    /** @var array<string, int|null> */
    protected array $contractsByNumber = [];

    /** @var array<int, int|null> */
    protected array $installmentsById = [];

    /** @var array<int, array<string, int|null>> */
    protected array $installmentsByContract = [];

    public function model(array $row)
    {
        if ($this->isRowEmpty($row)) {
            return null;
        }

        $this->rowCount++;

        try {
            $row = $this->normalizeRowKeys($row);

            $investorRaw    = Arr::get($row, 'investor_id');
            $statusRaw      = Arr::get($row, 'status_id');
            $bankRaw        = Arr::get($row, 'bank_account_id');
            $safeRaw        = Arr::get($row, 'safe_id');
            $amount         = (float) Arr::get($row, 'amount', 0);
            $dateValue      = Arr::get($row, 'transaction_date');
            $ref            = Arr::get($row, 'ref');
            $notes          = Arr::get($row, 'notes');
            $contractRaw    = Arr::get($row, 'contract_id');
            $installmentRaw = Arr::get($row, 'installment_id');

            $investorId = $this->resolveInvestorId($investorRaw);
            if (!$investorId) {
                throw new \RuntimeException(__('investors::investor_ledger_import.Investor missing', [
                    'value' => $this->stringifyValue($investorRaw),
                ]));
            }

            $status = $this->resolveStatus($statusRaw);
            if (!$status) {
                throw new \RuntimeException(__('investors::investor_ledger_import.Status missing', [
                    'value' => $this->stringifyValue($statusRaw),
                ]));
            }
            $statusId = (int) $status->getKey();

            $bankAccountId = $this->resolveBankAccountId($bankRaw);
            $safeId        = $this->resolveSafeId($safeRaw);

            if ($bankAccountId && $safeId) {
                throw new \RuntimeException(__('investors::investor_ledger_import.Bank or safe rule'));
            }

            if ($this->valueIsFilled($bankRaw) && !$bankAccountId) {
                throw new \RuntimeException(__('investors::investor_ledger_import.Bank account missing', [
                    'value' => $this->stringifyValue($bankRaw),
                ]));
            }

            if ($this->valueIsFilled($safeRaw) && !$safeId) {
                throw new \RuntimeException(__('investors::investor_ledger_import.Safe missing', [
                    'value' => $this->stringifyValue($safeRaw),
                ]));
            }

            $contractId = $this->resolveContractId($contractRaw);
            if ($this->valueIsFilled($contractRaw) && !$contractId) {
                throw new \RuntimeException(__('investors::investor_ledger_import.Contract missing', [
                    'value' => $this->stringifyValue($contractRaw),
                ]));
            }

            $installmentId = $this->resolveInstallmentId($installmentRaw, $contractId);
            if ($this->valueIsFilled($installmentRaw) && !$installmentId) {
                throw new \RuntimeException(__('investors::investor_ledger_import.Installment missing', [
                    'value' => $this->stringifyValue($installmentRaw),
                ]));
            }

            $date = $this->parseDateToString($dateValue);
            if (!$date) {
                throw new \RuntimeException(__('investors::investor_ledger_import.Invalid date'));
            }

            if ($amount <= 0) {
                throw new \RuntimeException(__('investors::investor_ledger_import.Invalid amount'));
            }

            $typeId = (int) ($status->transaction_type_id ?? 0);
            if ($typeId <= 0) {
                throw new \RuntimeException(__('investors::investor_ledger_import.Transaction type missing'));
            }

            $typeName  = TransactionType::whereKey($typeId)->value('name');
            $direction = $this->directionFromTypeName($typeName);
            if (!in_array($direction, ['in', 'out'], true)) {
                throw new \RuntimeException(__('investors::investor_ledger_import.Direction missing'));
            }

            $notesValue = $this->valueIsFilled($notes) ? trim((string) $notes) : null;
            $refValue   = $this->valueIsFilled($ref) ? trim((string) $ref) : null;

            $entry = DB::transaction(function () use (
                $date,
                $investorId,
                $statusId,
                $typeId,
                $bankAccountId,
                $safeId,
                $contractId,
                $installmentId,
                $amount,
                $direction,
                $refValue,
                $notesValue
            ) {
                $ledgerEntry = LedgerEntry::create([
                    'entry_date'            => $date,
                    'investor_id'           => $investorId,
                    'is_office'             => false,
                    'transaction_status_id' => $statusId,
                    'transaction_type_id'   => $typeId,
                    'bank_account_id'       => $bankAccountId ?: null,
                    'safe_id'               => $safeId ?: null,
                    'contract_id'           => $contractId ?: null,
                    'installment_id'        => $installmentId ?: null,
                    'amount'                => $amount,
                    'direction'             => $direction,
                    'ref'                   => $refValue,
                    'notes'                 => $notesValue,
                ]);

                InvestorTransaction::create([
                    'investor_id'      => $investorId,
                    'contract_id'      => $contractId ?: null,
                    'installment_id'   => $installmentId ?: null,
                    'status_id'        => $statusId,
                    'amount'           => $amount,
                    'transaction_date' => $date,
                    'notes'            => $notesValue,
                ]);

                return $ledgerEntry;
            });

            $this->insertedCount++;

            return $entry;
        } catch (\Throwable $e) {
            $this->skippedCount++;
            throw $e;
        }
    }

    public function onFailure(Failure ...$failures): void
    {
        $filtered = [];

        foreach ($failures as $failure) {
            $values = (array) $failure->values();

            if ($this->isRowEmpty($values)) {
                continue;
            }

            $filtered[] = $failure;
        }

        if ($filtered === []) {
            return;
        }

        $this->traitOnFailure(...$filtered);
    }

    public function rules(): array
    {
        return [
            '*.investor_id' => ['required', function ($attribute, $value, $fail) {
                if (!$this->resolveInvestorId($value)) {
                    $fail(__('investors::investor_ledger_import.Investor missing', [
                        'value' => $this->stringifyValue($value),
                    ]));
                }
            }],
            '*.status_id' => ['required', function ($attribute, $value, $fail) {
                if (!$this->resolveStatus($value)) {
                    $fail(__('investors::investor_ledger_import.Status missing', [
                        'value' => $this->stringifyValue($value),
                    ]));
                }
            }],
            '*.bank_account_id' => ['nullable', function ($attribute, $value, $fail) {
                if ($this->valueIsFilled($value) && !$this->resolveBankAccountId($value)) {
                    $fail(__('investors::investor_ledger_import.Bank account missing', [
                        'value' => $this->stringifyValue($value),
                    ]));
                }
            }],
            '*.safe_id' => ['nullable', function ($attribute, $value, $fail) {
                if ($this->valueIsFilled($value) && !$this->resolveSafeId($value)) {
                    $fail(__('investors::investor_ledger_import.Safe missing', [
                        'value' => $this->stringifyValue($value),
                    ]));
                }
            }],
            '*.amount'           => ['required', 'numeric', 'min:0.01'],
            '*.transaction_date' => ['required'],
            '*.contract_id' => ['nullable', function ($attribute, $value, $fail) {
                if ($this->valueIsFilled($value) && !$this->resolveContractId($value)) {
                    $fail(__('investors::investor_ledger_import.Contract missing', [
                        'value' => $this->stringifyValue($value),
                    ]));
                }
            }],
            '*.installment_id'   => ['nullable'],
            '*.ref'              => ['nullable', 'string', 'max:255'],
            '*.notes'            => ['nullable', 'string'],
        ];
    }

    public function prepareForValidation(array $data, int $index)
    {
        return $this->normalizeRowKeys($data);
    }

    public function customValidationAttributes()
    {
        return [
            'investor_id'      => __('investors::investor_ledger_import.fields.investor_id'),
            'status_id'        => __('investors::investor_ledger_import.fields.status_id'),
            'bank_account_id'  => __('investors::investor_ledger_import.fields.bank_account_id'),
            'safe_id'          => __('investors::investor_ledger_import.fields.safe_id'),
            'amount'           => __('investors::investor_ledger_import.fields.amount'),
            'transaction_date' => __('investors::investor_ledger_import.fields.transaction_date'),
            'contract_id'      => __('investors::investor_ledger_import.fields.contract_id'),
            'installment_id'   => __('investors::investor_ledger_import.fields.installment_id'),
            'ref'              => __('investors::investor_ledger_import.fields.ref'),
            'notes'            => __('investors::investor_ledger_import.fields.notes'),
        ];
    }

    // ملاحظة: لا نستخدم WithBatchInserts هنا لنفس السبب المذكور في LedgerEntriesImport:
    // عملية model() تحفظ السجلات مباشرةً، ووجود WithBatchInserts يدفع المكتبة
    // لمحاولة إدراجها مرة ثانية بنفس المفاتيح.
    public function chunkSize(): int
    {
        return 500;
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    public function getInsertedCount(): int
    {
        return $this->insertedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    protected function resolveInvestorId($value): ?int
    {
        if (!$this->valueIsFilled($value)) {
            return null;
        }

        $this->ensureInvestorsLoaded();

        if (is_numeric($value)) {
            $id = (int) $value;

            return $id > 0 ? ($this->investorsById[$id] ?? null) : null;
        }

        foreach ($this->nameKeys((string) $value) as $key) {
            if (array_key_exists($key, $this->investorsByName)) {
                return $this->investorsByName[$key] ?? null;
            }
        }

        return null;
    }

    protected function ensureInvestorsLoaded(): void
    {
        if ($this->investorsLoaded) {
            return;
        }

        Investor::query()
            ->select('id', 'name')
            ->orderBy('id')
            ->chunk(500, function ($chunk) {
                foreach ($chunk as $investor) {
                    $id = (int) $investor->getKey();
                    $this->investorsById[$id] = $id;

                    foreach ($this->nameKeys($investor->name) as $key) {
                        if (!array_key_exists($key, $this->investorsByName)) {
                            $this->investorsByName[$key] = $id;
                        }
                    }
                }
            });

        $this->investorsLoaded = true;
    }

    protected function resolveStatus($value): ?TransactionStatus
    {
        if (!$this->valueIsFilled($value)) {
            return null;
        }

        $this->ensureStatusesLoaded();

        if (is_numeric($value)) {
            $id = (int) $value;

            return $id > 0 ? ($this->statusesById[$id] ?? null) : null;
        }

        foreach ($this->nameKeys((string) $value) as $key) {
            if (array_key_exists($key, $this->statusesByName)) {
                return $this->statusesByName[$key];
            }
        }

        return null;
    }

    protected function ensureStatusesLoaded(): void
    {
        if ($this->statusesLoaded) {
            return;
        }

        TransactionStatus::query()
            ->select('id', 'name', 'transaction_type_id')
            ->whereIn('id', function ($q) {
                $q->select('transaction_status_id')
                    ->from('category_transaction_status')
                    ->where('category_id', $this->categoryInvestorsId);
            })
            ->when(!empty($this->statusesDisallowedNames), function ($query) {
                $query->whereNotIn('name', $this->statusesDisallowedNames);
            })
            ->orderBy('id')
            ->chunk(500, function ($chunk) {
                foreach ($chunk as $status) {
                    $id = (int) $status->getKey();
                    $this->registerStatus($status);
                }
            });

        $this->statusesLoaded = true;
    }

    protected function registerStatus(TransactionStatus $status): void
    {
        $id = (int) $status->getKey();

        $this->statusesById[$id] = $status;

        foreach ($this->nameKeys($status->name) as $key) {
            if (!array_key_exists($key, $this->statusesByName)) {
                $this->statusesByName[$key] = $status;
            }
        }
    }

    protected function resolveBankAccountId($value): ?int
    {
        if (!$this->valueIsFilled($value)) {
            return null;
        }

        $this->ensureBankAccountsLoaded();

        if (is_numeric($value)) {
            $id = (int) $value;

            return $id > 0 ? ($this->bankAccountsById[$id] ?? null) : null;
        }

        foreach ($this->nameKeys((string) $value) as $key) {
            if (array_key_exists($key, $this->bankAccountsByName)) {
                return $this->bankAccountsByName[$key] ?? null;
            }
        }

        return null;
    }

    protected function ensureBankAccountsLoaded(): void
    {
        if ($this->bankAccountsLoaded) {
            return;
        }

        BankAccount::query()
            ->select('id', 'name')
            ->orderBy('id')
            ->chunk(500, function ($chunk) {
                foreach ($chunk as $account) {
                    $id = (int) $account->getKey();
                    $this->bankAccountsById[$id] = $id;

                    foreach ($this->nameKeys($account->name) as $key) {
                        if (!array_key_exists($key, $this->bankAccountsByName)) {
                            $this->bankAccountsByName[$key] = $id;
                        }
                    }
                }
            });

        $this->bankAccountsLoaded = true;
    }

    protected function resolveSafeId($value): ?int
    {
        if (!$this->valueIsFilled($value)) {
            return null;
        }

        $this->ensureSafesLoaded();

        if (is_numeric($value)) {
            $id = (int) $value;

            return $id > 0 ? ($this->safesById[$id] ?? null) : null;
        }

        foreach ($this->nameKeys((string) $value) as $key) {
            if (array_key_exists($key, $this->safesByName)) {
                return $this->safesByName[$key] ?? null;
            }
        }

        return null;
    }

    protected function ensureSafesLoaded(): void
    {
        if ($this->safesLoaded) {
            return;
        }

        Safe::query()
            ->select('id', 'name')
            ->orderBy('id')
            ->chunk(500, function ($chunk) {
                foreach ($chunk as $safe) {
                    $id = (int) $safe->getKey();
                    $this->safesById[$id] = $id;

                    foreach ($this->nameKeys($safe->name) as $key) {
                        if (!array_key_exists($key, $this->safesByName)) {
                            $this->safesByName[$key] = $id;
                        }
                    }
                }
            });

        $this->safesLoaded = true;
    }

    protected function resolveContractId($value): ?int
    {
        if (!$this->valueIsFilled($value)) {
            return null;
        }

        if (is_numeric($value)) {
            $id = (int) $value;

            if ($id <= 0) {
                return null;
            }

            if (!array_key_exists($id, $this->contractsById)) {
                $contract = Contract::query()
                    ->select('id', 'contract_number')
                    ->find($id);

                if ($contract) {
                    $this->registerContract($contract);
                } else {
                    $this->contractsById[$id] = null;
                }
            }

            return $this->contractsById[$id] ?? null;
        }

        $keys = $this->codeKeys($value);

        foreach ($keys as $key) {
            if (array_key_exists($key, $this->contractsByNumber)) {
                return $this->contractsByNumber[$key] ?? null;
            }
        }

        if (empty($keys)) {
            return null;
        }

        $query = Contract::query()
            ->select('id', 'contract_number');

        $first = true;
        foreach ($keys as $key) {
            if ($first) {
                $query->where('contract_number', $key);
                $first = false;
            } else {
                $query->orWhere('contract_number', $key);
            }
        }

        $contract = $query->first();

        if ($contract) {
            $this->registerContract($contract);
            return (int) $contract->getKey();
        }

        foreach ($keys as $key) {
            if (!array_key_exists($key, $this->contractsByNumber)) {
                $this->contractsByNumber[$key] = null;
            }
        }

        return null;
    }

    protected function resolveInstallmentId($value, ?int $contractId): ?int
    {
        if (!$this->valueIsFilled($value)) {
            return null;
        }

        if (is_numeric($value)) {
            $id = (int) $value;

            if ($id > 0) {
                if (!array_key_exists($id, $this->installmentsById)) {
                    $installment = ContractInstallment::query()
                        ->select('id', 'contract_id', 'installment_number')
                        ->find($id);

                    if ($installment) {
                        $this->registerInstallment($installment);
                    } else {
                        $this->installmentsById[$id] = null;
                    }
                }

                if (isset($this->installmentsById[$id])) {
                    return $this->installmentsById[$id] ?: null;
                }
            }
        }

        if (!$contractId) {
            return null;
        }

        $keys = $this->codeKeys($value);

        foreach ($keys as $key) {
            if (isset($this->installmentsByContract[$contractId][$key])) {
                return $this->installmentsByContract[$contractId][$key] ?? null;
            }
        }

        if (empty($keys)) {
            return null;
        }

        $query = ContractInstallment::query()
            ->select('id', 'contract_id', 'installment_number')
            ->where('contract_id', $contractId);

        $first = true;
        foreach ($keys as $key) {
            if ($first) {
                $query->where('installment_number', $key);
                $first = false;
            } else {
                $query->orWhere('installment_number', $key);
            }
        }

        $installment = $query->first();

        if ($installment) {
            $this->registerInstallment($installment);
            return (int) $installment->getKey();
        }

        foreach ($keys as $key) {
            $this->installmentsByContract[$contractId][$key] = null;
        }

        return null;
    }

    protected function registerContract(Contract $contract): void
    {
        $id = (int) $contract->getKey();
        $this->contractsById[$id] = $id;

        foreach ($this->codeKeys($contract->contract_number) as $key) {
            if (!array_key_exists($key, $this->contractsByNumber)) {
                $this->contractsByNumber[$key] = $id;
            }
        }
    }

    protected function registerInstallment(ContractInstallment $installment): void
    {
        $id = (int) $installment->getKey();
        $contractId = (int) $installment->contract_id;

        $this->installmentsById[$id] = $id;

        foreach ($this->codeKeys($installment->installment_number) as $key) {
            if (!isset($this->installmentsByContract[$contractId][$key])) {
                $this->installmentsByContract[$contractId][$key] = $id;
            }
        }
    }

    /**
     * @return array<int, string>
     */
    protected function nameKeys(?string $value): array
    {
        if ($value === null) {
            return [];
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return [];
        }

        $lower = mb_strtolower($trimmed, 'UTF-8');
        $normalized = $this->arNormalize($trimmed);

        return array_values(array_unique(array_filter([
            $trimmed,
            $lower,
            $normalized,
        ], static fn ($v) => $v !== '')));
    }

    /**
     * @param  mixed  $value
     * @return array<int, string>
     */
    protected function codeKeys($value): array
    {
        if ($value === null) {
            return [];
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === '' || $value === null) {
            return [];
        }

        $string = (string) $value;
        $lower = mb_strtolower($string, 'UTF-8');
        $normalized = $this->normalizeCode($value);

        return array_values(array_unique(array_filter([
            $string,
            $lower,
            $normalized,
        ], static fn ($v) => $v !== '')));
    }

    /**
     * @param  mixed  $value
     */
    protected function valueIsFilled($value): bool
    {
        if (is_string($value)) {
            return trim($value) !== '';
        }

        return !in_array($value, [null, ''], true);
    }

    /**
     * @param  mixed  $value
     */
    protected function stringifyValue($value): string
    {
        if (is_scalar($value)) {
            return trim((string) $value);
        }

        return '';
    }

    /**
     * @param  mixed  $value
     */
    protected function normalizeCode($value): string
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === null || $value === '') {
            return '';
        }

        if (is_numeric($value)) {
            $number = (float) $value;

            if ((int) $number == $number) {
                return (string) (int) $number;
            }

            return (string) $number;
        }

        return mb_strtolower((string) $value, 'UTF-8');
    }

    protected function parseDateToString($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value) && (float) $value > 10000) {
            try {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value);
                return Carbon::instance($dt)->format('Y-m-d');
            } catch (\Throwable $e) {
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function arNormalize(?string $text): string
    {
        if ($text === null) {
            return '';
        }

        $text = trim(mb_strtolower($text, 'UTF-8'));

        return strtr($text, [
            'أ' => 'ا',
            'إ' => 'ا',
            'آ' => 'ا',
            'ة' => 'ه',
            'ى' => 'ي',
            'ؤ' => 'و',
            'ئ' => 'ي',
        ]);
    }

    protected function directionFromTypeName(?string $typeName): ?string
    {
        if ($typeName === null) {
            return null;
        }

        $name = $this->arNormalize($typeName);

        $exact = [
            'ايداع'   => 'in',
            'إيداع'   => 'in',
            'توريد'   => 'in',
            'تحصيل'  => 'in',
            'سحب'     => 'out',
            'صرف'     => 'out',
            'توزيع'   => 'out',
            'استرداد' => 'out',
            'deposit' => 'in',
            'withdraw'=> 'out',
        ];

        if (isset($exact[$typeName])) {
            return $exact[$typeName];
        }

        if (str_contains($name, 'ايداع') || str_contains($name, 'توريد') || str_contains($name, 'تحصيل')) {
            return 'in';
        }

        if (str_contains($name, 'سحب') || str_contains($name, 'صرف') || str_contains($name, 'توزيع') || str_contains($name, 'استرداد')) {
            return 'out';
        }

        if (str_contains($name, 'deposit')) {
            return 'in';
        }

        if (str_contains($name, 'withdraw')) {
            return 'out';
        }

        return null;
    }

    /**
     * تطبيع رؤوس الأعمدة إلى المفاتيح الأساسية (investor_id, status_id, ...).
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function normalizeRowKeys(array $row): array
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

                if (array_key_exists($alias, $row)) {
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
    protected function headingAliasMap(): array
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
    protected function canonicalHeadingKeys(): array
    {
        return [
            'investor_id',
            'status_id',
            'bank_account_id',
            'safe_id',
            'amount',
            'transaction_date',
            'contract_id',
            'installment_id',
            'ref',
            'notes',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function headingTranslations(string $key): array
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
    protected function slugVariants(string $value): array
    {
        $variants = [];

        foreach (['-', '_'] as $separator) {
            $slug = Str::slug($value, $separator);

            if ($slug !== '') {
                $variants[] = $slug;
                $variants[] = str_replace('-', '_', $slug);
            }
        }

        return array_values(array_unique(array_filter($variants)));
    }

    /**
     * @param  array<int, string>  $aliases
     * @return array<int, string>
     */
    protected function uniqueHeadingAliases(array $aliases): array
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
}
