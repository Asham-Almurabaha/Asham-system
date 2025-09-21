<?php

namespace Modules\Contracts\Imports;

use App\Imports\Concerns\DetectsEmptyRows;
use Modules\Contracts\Entities\Contract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Contracts\Imports\Concerns\HandlesContractInvestors;

class ContractInvestorsImport implements ToCollection, WithHeadingRow
{
    use SkipsErrors, SkipsFailures, HandlesContractInvestors, DetectsEmptyRows;

    private int $rows = 0;
    private int $updated = 0;
    private int $skipped = 0;

    /** @var array<int,array{row:int,attribute?:string|array,values:array,messages:array}> */
    private array $failuresSimple = [];
    /** @var string[] */
    private array $errorsSimple = [];
    /** @var array<int,array{row:int,values:array,reason:string}> */
    private array $skippedSimple = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $i => $raw) {
            $rowNum = $i + 2;
            $data = $raw->toArray();

            if ($this->isRowEmpty($data)) {
                continue;
            }

            $this->rows++;

            try {
                DB::transaction(function () use ($data, $rowNum) {
                    $number = $data['contract_number'] ?? null;
                    if (!$number) {
                        $this->skipped++;
                        $this->pushFailure($rowNum, 'contract_number', $data, ['مطلوب.']);
                        return;
                    }

                    $contract = Contract::where('contract_number', $number)->first();
                    if (!$contract) {
                        $this->skipped++;
                        $this->pushFailure($rowNum, 'contract_number', $data, ['غير موجود.']);
                        return;
                    }

                    $investors = $this->parseInvestorsFlexible($data);
                    $this->attachInvestorsAndAutoStatus($contract, $investors);
                    $this->updated++;
                });
            } catch (\Throwable $e) {
                $this->skipped++;
                $this->errorsSimple[] = "صف {$rowNum}: " . $e->getMessage();
                $this->pushFailure($rowNum, '*', $data, [$e->getMessage()]);
            }
        }
    }

    private function pushFailure(int $row, string|array $attribute, array $values, array $messages): void
    {
        $this->failuresSimple[] = [
            'row' => $row,
            'attribute' => $attribute,
            'values' => $values,
            'messages' => $messages,
        ];

        $this->skippedSimple[] = [
            'row'    => $row,
            'values' => $values,
            'reason' => implode(' | ', $messages),
        ];
    }

    public function getRowCount(): int { return $this->rows; }
    public function getUpdatedCount(): int { return $this->updated; }
    public function getSkippedCount(): int { return $this->skipped; }
    public function getFailuresSimple(): array { return $this->failuresSimple; }
    public function getErrorsSimple(): array { return $this->errorsSimple; }
    public function skipped(): array { return $this->skippedSimple; }
}
