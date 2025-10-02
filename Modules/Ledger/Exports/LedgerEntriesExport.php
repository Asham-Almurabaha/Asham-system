<?php

namespace Modules\Ledger\Exports;

use App\Support\ExcelHeadingLocalizer;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Ledger\Entities\LedgerEntry;

class LedgerEntriesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(protected array $filters = [])
    {
    }

    /**
     * @var array<int, string>
     */
    protected array $columns = [
        'id',
        'party_category',
        'investor_id',
        'investor_name',
        'status_id',
        'status_name',
        'transaction_type',
        'direction',
        'amount',
        'transaction_date',
        'bank_account_id',
        'bank_account_name',
        'safe_id',
        'safe_name',
        'contract_id',
        'installment_id',
        'ref',
        'notes',
        'product_type_id',
        'product_type_name',
        'quantity',
        'created_at',
    ];

    public function query(): Builder
    {
        $query = LedgerEntry::query()
            ->with(['investor', 'status', 'type', 'bankAccount', 'safe', 'productTransactions.productType'])
            ->orderBy('entry_date')
            ->orderBy('id');

        $category = $this->filters['party_category'] ?? null;
        if ($category === 'investors') {
            $query->where('is_office', false);
        } elseif ($category === 'office') {
            $query->where('is_office', true)->whereNull('company_transaction_id');
        } elseif ($category === 'companies') {
            $query->whereNotNull('company_transaction_id');
        }

        if (!empty($this->filters['investor_id'])) {
            $query->where('investor_id', $this->filters['investor_id']);
        }

        if (!empty($this->filters['status_id'])) {
            $query->where('transaction_status_id', $this->filters['status_id']);
        }

        if (!empty($this->filters['account_type'])) {
            if ($this->filters['account_type'] === 'bank') {
                $query->whereNotNull('bank_account_id')->whereNull('safe_id');
            } elseif ($this->filters['account_type'] === 'safe') {
                $query->whereNotNull('safe_id')->whereNull('bank_account_id');
            }
        }

        if (!empty($this->filters['from'])) {
            $query->whereDate('entry_date', '>=', $this->filters['from']);
        }

        if (!empty($this->filters['to'])) {
            $query->whereDate('entry_date', '<=', $this->filters['to']);
        }

        if (!empty($this->filters['bank_ids']) && is_array($this->filters['bank_ids'])) {
            $query->whereIn('bank_account_id', array_filter($this->filters['bank_ids']));
        }

        if (!empty($this->filters['safe_ids']) && is_array($this->filters['safe_ids'])) {
            $query->whereIn('safe_id', array_filter($this->filters['safe_ids']));
        }

        return $query;
    }

    /**
     * @param  \Modules\Ledger\Entities\LedgerEntry  $entry
     * @return array<int, mixed>
     */
    public function map($entry): array
    {
        $product = $entry->productTransactions->first();

        $productType = $product?->productType;

        return [
            $entry->id,
            $entry->party_category,
            $entry->investor_id,
            optional($entry->investor)->name,
            $entry->transaction_status_id,
            optional($entry->status)->name,
            optional($entry->type)->name,
            $entry->direction,
            (float) $entry->amount,
            optional($entry->entry_date)->format('Y-m-d'),
            $entry->bank_account_id,
            optional($entry->bankAccount)->name,
            $entry->safe_id,
            optional($entry->safe)->name,
            $entry->contract_id,
            $entry->installment_id,
            $entry->ref,
            $entry->notes,
            $product?->product_type_id,
            optional($productType)->name,
            $product?->quantity,
            optional($entry->created_at)->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ExcelHeadingLocalizer::translateMany($this->columns);
    }
}
