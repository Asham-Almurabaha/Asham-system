<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LedgerEntriesFailuresFixExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(private array $failures) {}

    public function headings(): array
    {
        return [
            'party_category',   // investors | office
            'investor_id',      // مطلوب لو party_category=investors
            'status_id',
            'bank_account_id',  // اختر بنك أو خزنة فقط
            'safe_id',
            'amount',
            'transaction_date', // Y-m-d
            'contract_id',
            'installment_id',
            'ref',
            'notes',
            'errors',           // رسائل الخطأ للصف
            'source_row',       // رقم الصف الأصلي
        ];
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->failures as $f) {
            $vals = (array)($f['values'] ?? []);
            $msgs = $f['messages'] ?? ($f['errors'] ?? []);

            $rows[] = [
                $vals['party_category']   ?? ($vals['الفئة'] ?? ''),
                $vals['investor_id']      ?? ($vals['المستثمر'] ?? ''),
                $vals['status_id']        ?? ($vals['الحالة'] ?? ''),
                $vals['bank_account_id']  ?? ($vals['الحساب_البنكي'] ?? ''),
                $vals['safe_id']          ?? ($vals['الخزنة'] ?? ''),
                $vals['amount']           ?? ($vals['المبلغ'] ?? ''),
                $vals['transaction_date'] ?? ($vals['تاريخ_العملية'] ?? ''),
                $vals['contract_id']      ?? ($vals['العقد'] ?? ''),
                $vals['installment_id']   ?? ($vals['القسط'] ?? ''),
                $vals['ref']              ?? ($vals['المرجع'] ?? ''),
                $vals['notes']            ?? ($vals['ملاحظات'] ?? ''),
                is_array($msgs) ? implode(' | ', $msgs) : (string) $msgs,
                (int)($f['row'] ?? 0),
            ];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // 13 عمودًا: A..M (الـ errors = L)
        $sheet->getStyle('A1:M1')->getFont()->setBold(true);
        $sheet->getStyle('L:L')->getAlignment()->setWrapText(true);
        $sheet->freezePane('A2');
        return [];
    }
}
