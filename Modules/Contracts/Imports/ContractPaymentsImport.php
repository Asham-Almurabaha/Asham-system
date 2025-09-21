<?php

namespace Modules\Contracts\Imports;

use App\Imports\Concerns\DetectsEmptyRows;
use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Imports\Concerns\HandlesContractPayments;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ContractPaymentsImport implements ToCollection, WithHeadingRow, WithChunkReading, WithBatchInserts
{
    use SkipsErrors, SkipsFailures, HandlesContractPayments, DetectsEmptyRows;

    private int $rows = 0;
    private int $inserted = 0;
    private int $skipped = 0;

    /** @var array<int,array{row:int,values:array,reason:string}> */
    private array $skippedSimple = [];

    /** @var array<int,array{row:int,attribute?:string|array,values:array,messages:array}> */
    private array $failuresSimple = [];
    /** @var string[] */
    private array $errorsSimple = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $i => $raw) {
            $rowNum = $i + 2; // مع صف العناوين
            $data   = $raw->toArray();

            if ($this->isRowEmpty($data)) {
                continue;
            }

            $this->rows++;

            try {
                $number = $data['contract_number'] ?? null;
                if (!$number) {
                    $this->skipped++;
                    $this->pushFailure($rowNum, 'contract_number', $data, ['مفقود.']);
                    continue;
                }

                /** @var Contract|null $contract */
                $contract = Contract::where('contract_number', $number)->first();
                if (!$contract) {
                    $this->skipped++;
                    $this->pushFailure($rowNum, 'contract_number', $data, ['غير موجود.']);
                    continue;
                }

                $previousCumulative = isset($data['previous_cumulative'])
                    ? (float) $data['previous_cumulative']
                    : 0.0;

                $payments = $this->parsePaymentsFlexible($data);
                if (empty($payments) && $previousCumulative <= 0) {
                    $this->skipped++;

                    if ($this->hasAnyPaymentInput($data)) {
                        $this->pushFailure($rowNum, 'payments', $data, ['لا توجد سدادات صالحة.']);
                    } else {
                        $this->pushSkippedSimple($rowNum, $data, ['لا توجد بيانات سداد في هذا الصف.']);
                    }

                    continue;
                }

                DB::transaction(function () use ($contract, $payments, $previousCumulative) {
                    $appliedCount = 0;

                    if ($previousCumulative > 0) {
                        $appliedCount += $this->allocatePreviousCumulative($contract, $previousCumulative);
                    }

                    if (!empty($payments)) {
                        $this->createPaymentLedgerEntries($contract, $payments);
                        $this->allocatePaymentsToInstallments($contract, $payments);
                        $appliedCount += count($payments);
                    }

                    $this->inserted += $appliedCount;
                });
            } catch (\Throwable $e) {
                $this->skipped++;
                $this->errorsSimple[] = "صف {$rowNum}: " . $e->getMessage();
                $this->pushFailure($rowNum, '*', $data, [$e->getMessage()]);
            }
        }
    }

    private function pushFailure(int $row, string $attr, array $vals, array $messages): void
    {
        $this->failuresSimple[] = [
            'row' => $row,
            'attribute' => $attr,
            'values' => $vals,
            'messages' => $messages,
        ];

        $this->pushSkippedSimple($row, $vals, $messages);
    }

    private function pushSkippedSimple(int $row, array $values, array $messages): void
    {
        $reason = implode(' | ', array_map(static fn($msg) => (string) $msg, $messages));

        $this->skippedSimple[] = [
            'row'    => $row,
            'values' => $values,
            'reason' => $reason,
        ];
    }

    private function hasAnyPaymentInput(array $data): bool
    {
        $raw = $data['payments'] ?? null;
        if (is_string($raw)) {
            $raw = trim($raw);
            if ($raw !== '') {
                foreach (explode('|', $raw) as $chunk) {
                    $chunk = trim($chunk);
                    if ($chunk === '') {
                        continue;
                    }

                    if (str_contains($chunk, '#')) {
                        [$chunk] = array_map('trim', explode('#', $chunk, 2));
                    }

                    if ($chunk === '') {
                        continue;
                    }

                    [$left, $right] = array_pad(array_map('trim', explode(':', $chunk, 2)), 2, null);

                    $leftHasValue = $left !== null && $left !== '';
                    $rightHasValue = $right !== null && $right !== '';

                    $leftIsDate = $leftHasValue && strtotime($left) !== false;
                    $rightIsDate = $rightHasValue && strtotime($right) !== false;

                    if ($leftHasValue) {
                        if (is_numeric($left)) {
                            if ((float) $left > 0) {
                                return true;
                            }
                        } elseif (!$leftIsDate) {
                            return true;
                        }
                    }

                    if ($rightHasValue) {
                        if (is_numeric($right)) {
                            if ((float) $right > 0) {
                                return true;
                            }
                        } elseif (!$rightIsDate) {
                            return true;
                        }
                    }

                    if (($leftIsDate && !$rightHasValue) || ($rightIsDate && !$leftHasValue)) {
                        return true;
                    }
                }
            }
        } elseif (is_numeric($raw) && (float) $raw > 0) {
            return true;
        }

        for ($n = 1; $n <= 18; $n++) {
            $amountKeys = [
                "payment{$n}_amount", "payment{$n}_value",
                "installment{$n}_amount", "installment{$n}_value",
                "qist{$n}_amount", "qist{$n}_value",
                "qst{$n}_amount", "qst{$n}_value",
                "qest{$n}_amount", "qest{$n}_value",
            ];

            foreach ($amountKeys as $key) {
                if (!array_key_exists($key, $data)) {
                    continue;
                }

                $value = $data[$key];
                if (is_string($value)) {
                    $value = trim($value);
                }

                if ($value === '' || $value === null) {
                    continue;
                }

                if (is_numeric($value)) {
                    if ((float) $value > 0) {
                        return true;
                    }

                    continue;
                }

                return true;
            }
        }

        $firstPaymentKeys = ['down_payment', 'first_payment_amount'];
        foreach ($firstPaymentKeys as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];
            if (is_string($value)) {
                $value = trim($value);
            }

            if ($value === '' || $value === null) {
                continue;
            }

            if (is_numeric($value)) {
                if ((float) $value > 0) {
                    return true;
                }

                continue;
            }

            return true;
        }

        return false;
    }

    private function toDate($v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }

        if (is_numeric($v) && (float) $v > 10000) {
            try {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $v);
                return Carbon::instance($dt)->format('Y-m-d');
            } catch (\Throwable) {
                // fall back to generic parsing below
            }
        }

        try {
            return Carbon::parse($v)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    // ===== Counters =====
    public function getRowCount(): int { return $this->rows; }
    public function getInsertedCount(): int { return $this->inserted; }
    public function getSkippedCount(): int { return $this->skipped; }
    public function getFailuresSimple(): array { return $this->failuresSimple; }
    public function getErrorsSimple(): array { return $this->errorsSimple; }
    public function skipped(): array { return $this->skippedSimple; }

    public function chunkSize(): int { return 500; }
    public function batchSize(): int { return 500; }
}

