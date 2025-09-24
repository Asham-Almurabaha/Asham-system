<?php

namespace Modules\Contracts\Exports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Contracts\Entities\Contract;

class ContractsDataExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
     * @var array<int, string>
     */
    protected array $columns;

    /**
     * @var array<int, string>
     */
    protected array $extraColumns = [
        'investors_count',
        'investors_details',
    ];

    /**
     * @var array<string, array{heading_key: string, relation: string}>
     */
    protected array $relationColumns = [
        'customer_id' => ['heading_key' => 'customer', 'relation' => 'customer'],
        'guarantor_id' => ['heading_key' => 'guarantor', 'relation' => 'guarantor'],
        'contract_status_id' => ['heading_key' => 'contract_status', 'relation' => 'contractStatus'],
        'product_type_id' => ['heading_key' => 'product_type', 'relation' => 'productType'],
        'installment_type_id' => ['heading_key' => 'installment_type', 'relation' => 'installmentType'],
    ];

    public function __construct(protected Builder $query)
    {
        $this->columns = Schema::getColumnListing((new Contract())->getTable());
    }

    public function query(): Builder
    {
        $relations = collect($this->relationColumns)
            ->pluck('relation')
            ->push('investors')
            ->unique()
            ->filter()
            ->values()
            ->all();

        return (clone $this->query)
            ->with($relations)
            ->select($this->columns)
            ->orderBy('id');
    }

    /**
     * @param  \Modules\Contracts\Entities\Contract  $contract
     * @return array<int, mixed>
     */
    public function map($contract): array
    {
        $row = array_map(function (string $column) use ($contract) {
            if (isset($this->relationColumns[$column])) {
                $relationName = $this->relationColumns[$column]['relation'];

                return optional($contract->{$relationName})->name;
            }

            return $contract->getAttribute($column);
        }, $this->columns);

        $row[] = $contract->investors->count();
        $row[] = $contract->investors
            ->map(function ($investor) {
                $name = $investor->name ?? ('#' . $investor->id);
                $share = $investor->pivot->share_percentage ?? null;

                if ($share === null) {
                    return $name;
                }

                return sprintf('%s (%s%%)', $name, rtrim(rtrim(number_format((float) $share, 2, '.', ''), '0'), '.'));
            })
            ->filter()
            ->implode('; ');

        return $row;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        $baseHeadings = array_map(function (string $column) {
            if (isset($this->relationColumns[$column])) {
                return $this->getHeadingLabel($this->relationColumns[$column]['heading_key']);
            }

            return $this->getHeadingLabel($column);
        }, $this->columns);

        $extraHeadings = array_map(fn (string $column) => $this->getHeadingLabel($column), $this->extraColumns);

        return array_merge($baseHeadings, $extraHeadings);
    }

    protected function getHeadingLabel(string $column): string
    {
        $translationKey = "contracts::contracts.export_headings.{$column}";

        if (Lang::has($translationKey)) {
            return trans($translationKey);
        }

        return ucwords(str_replace('_', ' ', $column));
    }
}
