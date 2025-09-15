<?php

namespace Modules\Contracts\Imports;

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
    use SkipsErrors, SkipsFailures, HandlesContractPayments;

    private int $rows = 0;
    private int $inserted = 0;
    private int $skipped = 0;

    /** @var array<int,array{row:int,attribute?:string|array,values:array,messages:array}> */
    private array $failuresSimple = [];
    /** @var string[] */
    private array $errorsSimple = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $i => $raw) {
            $this->rows++;
            $rowNum = $i + 2; // مع صف العناوين
            $data   = $raw->toArray();

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

                $payments = $this->parsePaymentsFlexible($data);
                if (empty($payments)) {
                    $this->skipped++;
                    $this->pushFailure($rowNum, 'payments', $data, ['لا توجد سدادات صالحة.']);
                    continue;
                }

                DB::transaction(function () use ($contract, $payments) {
                    $this->createPaymentLedgerEntries($contract, $payments);
                    $this->allocatePaymentsToInstallments($contract, $payments);
                    $this->inserted += count($payments);
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

    public function chunkSize(): int { return 500; }
    public function batchSize(): int { return 500; }
}

