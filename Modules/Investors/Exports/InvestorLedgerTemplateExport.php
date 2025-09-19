<?php

namespace Modules\Investors\Exports;

use App\Support\ExcelHeadingLocalizer;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InvestorLedgerTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ExcelHeadingLocalizer::translateMany([
            'investor_id',
            'status_id',
            'bank_account_id',
            'safe_id',
            'amount',
            'transaction_date',
            'ref',
            'notes',
            'contract_id',
            'installment_id',
        ]);
    }

    public function array(): array
    {
        return [
            [
                'مستثمر تجريبي 1',      // investor (ID or name)
                'تحصيل أرباح',          // status (ID or name)
                'الحساب البنكي الرئيسي',// bank account (ID or name)
                '',                      // safe (ID or name)
                1500.00,                 // amount
                '2025-09-02',            // transaction_date
                'INV5-SEP',              // ref
                'تحصيل أرباح المستثمر التجريبي لشهر 9',
                'CN-1001',               // contract (ID or number)
                '1',                     // installment (ID or number)
            ],
            [
                8,            // investor_id
                14,           // status_id
                '',           // bank_account_id
                2,            // safe_id
                2200.00,      // amount
                '2025-09-05', // transaction_date
                'INV8-WD',    // ref
                'سحب أرباح للمستثمر #8',
                '',
                '',
            ],
        ];
    }
}
