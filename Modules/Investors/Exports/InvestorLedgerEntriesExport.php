<?php

namespace Modules\Investors\Exports;

use App\Support\ExcelHeadingLocalizer;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Ledger\Entities\LedgerEntry;

class InvestorLedgerEntriesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
     * @var array<int, string>
     */
    protected array $columns = [
        'id',
        'investor_id',
        'investor_name',
        'status_id',
        'status_name',
        'transaction_type',
        'direction',
        'amount',
        'transaction_date',
        'bank_account_name',
        'safe_name',
        'contract_id',
        'installment_id',
        'ref',
        'notes',
        'created_at',
    ];

    public function query(): Builder
    {
        return LedgerEntry::query()
            ->with(['investor', 'status', 'type', 'bankAccount', 'safe'])
            ->where('is_office', false)
            ->orderBy('entry_date')
            ->orderBy('id');
    }

    /**
     * @param  \Modules\Ledger\Entities\LedgerEntry  $entry
     * @return array<int, mixed>
     */
    public function map($entry): array
    {
        return [
            $entry->id,
            $entry->investor_id,
            optional($entry->investor)->name,
            $entry->transaction_status_id,
            optional($entry->status)->name,
            optional($entry->type)->name,
            $entry->direction,
            (float) $entry->amount,
            optional($entry->entry_date)?->format('Y-m-d'),
            optional($entry->bankAccount)->name,
            optional($entry->safe)->name,
            $entry->contract_id,
            $entry->installment_id,
            $entry->ref,
            $entry->notes,
            optional($entry->created_at)?->format('Y-m-d H:i:s'),
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
