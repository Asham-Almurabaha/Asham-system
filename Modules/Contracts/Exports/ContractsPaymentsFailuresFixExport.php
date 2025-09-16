<?php

namespace Modules\Contracts\Exports;

use App\Support\ExcelHeadingLocalizer;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ContractsPaymentsFailuresFixExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    /** @var array<int, array<string, mixed>> */
    private array $rows;

    /** @var string[] */
    private array $headingKeys;

    /**
     * @param array<int, array<string, mixed>> $failures
     */
    public function __construct(array $failures)
    {
        $this->rows = $failures;
        $this->headingKeys = $this->buildHeadings($failures);
    }

    public function headings(): array
    {
        return ExcelHeadingLocalizer::translateMany($this->headingKeys);
    }

    public function array(): array
    {
        $output = [];

        foreach ($this->rows as $failure) {
            $values   = (array)($failure['values'] ?? []);
            $messages = $failure['messages'] ?? ($failure['errors'] ?? '');

            $row = [];

        foreach ($this->headingKeys as $heading) {
                if ($heading === '__errors') {
                    $row[] = is_array($messages) ? implode(' | ', $messages) : (string) $messages;
                    continue;
                }

                if ($heading === '__row') {
                    $row[] = (int) ($failure['row'] ?? 0);
                    continue;
                }

                $value = $values[$heading] ?? '';

                if ($value instanceof \Illuminate\Support\Collection) {
                    $value = $value->all();
                }

                if (is_array($value)) {
                    $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $row[] = $encoded === false ? '' : $encoded;
                    continue;
                }

                if ($value instanceof \Stringable || (is_object($value) && method_exists($value, '__toString'))) {
                    $row[] = (string) $value;
                    continue;
                }

                if (is_bool($value)) {
                    $row[] = $value ? '1' : '0';
                    continue;
                }

                if ($value === null) {
                    $row[] = '';
                    continue;
                }

                $row[] = (string) $value;
            }

            $output[] = $row;
        }

        return $output;
    }

    public function styles(Worksheet $sheet)
    {
        if (empty($this->headingKeys)) {
            return [];
        }

        $highestColumn = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$highestColumn}1")->getFont()->setBold(true);

        $lastColumnIndex   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        $errorsColumnLetter= \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(max(1, $lastColumnIndex - 1));
        $sheet->getStyle("{$errorsColumnLetter}:{$errorsColumnLetter}")->getAlignment()->setWrapText(true);

        $sheet->freezePane('A2');

        return [];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return string[]
     */
    private function buildHeadings(array $rows): array
    {
        $preferred = [
            'contract_number',
            'payments',
            'down_payment',
            'down_payment_date',
            'first_payment_amount',
            'first_payment_date',
        ];

        for ($i = 1; $i <= 18; $i++) {
            $preferred[] = "payment{$i}_amount";
            $preferred[] = "payment{$i}_date";
            $preferred[] = "payment{$i}_notes";
        }

        $existingKeys = [];
        foreach ($rows as $row) {
            foreach (array_keys((array)($row['values'] ?? [])) as $key) {
                $existingKeys[$key] = true;
            }
        }

        $headings = [];
        $added = [];
        $add = static function (string $key) use (&$headings, &$added) {
            if ($key === '') {
                return;
            }
            if (isset($added[$key])) {
                return;
            }
            $headings[] = $key;
            $added[$key] = true;
        };

        foreach ($preferred as $key) {
            $add($key);
        }

        foreach (array_keys($existingKeys) as $key) {
            $add($key);
        }

        $add('__errors');
        $add('__row');

        return $headings;
    }
}
