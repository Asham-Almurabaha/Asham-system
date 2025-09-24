<?php

namespace Modules\Customers\Exports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Customers\Entities\Customer;

class CustomersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
     * @var array<int, string>
     */
    protected array $columns;

    /**
     * @var array<string, array{heading_key: string, relation: string}>
     */
    protected array $relationColumns = [
        'title_id' => ['heading_key' => 'title', 'relation' => 'title'],
        'customer_status_id' => ['heading_key' => 'customer_status', 'relation' => 'customerStatus'],
        'nationality_id' => ['heading_key' => 'nationality', 'relation' => 'nationality'],
    ];

    public function __construct()
    {
        $this->columns = Schema::getColumnListing((new Customer())->getTable());
    }

    public function query(): Builder
    {
        return Customer::query()
            ->with(
                collect($this->relationColumns)
                    ->pluck('relation')
                    ->all()
            )
            ->select($this->columns)
            ->orderBy('id');
    }

    /**
     * @param  \Modules\Customers\Entities\Customer  $customer
     * @return array<int, mixed>
     */
    public function map($customer): array
    {
        return array_map(function (string $column) use ($customer) {
            if (isset($this->relationColumns[$column])) {
                $relationName = $this->relationColumns[$column]['relation'];

                return optional($customer->{$relationName})->name;
            }

            return $customer->getAttribute($column);
        }, $this->columns);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return array_map(function (string $column) {
            if (isset($this->relationColumns[$column])) {
                return $this->getHeadingLabel($this->relationColumns[$column]['heading_key']);
            }

            return $this->getHeadingLabel($column);
        }, $this->columns);
    }

    protected function getHeadingLabel(string $column): string
    {
        $translationKey = "customers::messages.export_headings.{$column}";

        if (Lang::has($translationKey)) {
            return trans($translationKey);
        }

        return ucwords(str_replace('_', ' ', $column));
    }
}

