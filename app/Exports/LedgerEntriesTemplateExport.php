<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LedgerEntriesTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        // ملاحظات:
        // - party_category: investors | office
        // - لازم تختار إمّا bank_account_id أو safe_id (واحد فقط)
        // - transaction_date بصيغة Y-m-d
        // - product_type_id و quantity تُستخدم فقط مع حالات البضائع (شراء/بيع بضائع)
        return [
            'party_category',    // إلزامي: investors | office
            'investor_id',       // مطلوب إذا كانت الفئة investors
            'status_id',         // إلزامي: ID من transaction_statuses
            'bank_account_id',   // اختياري (واحد من البنك/الخزنة)
            'safe_id',           // اختياري (واحد من البنك/الخزنة)
            'amount',            // إلزامي (>= 0.01)
            'transaction_date',  // إلزامي (Y-m-d)
            'notes',             // اختياري
            'contract_id',       // اختياري (للربط إن وُجد)
            'installment_id',    // اختياري (للربط إن وُجد)
            'ref',               // اختياري
            'product_type_id',   // اختياري (لبنود البضائع)
            'quantity',          // اختياري (لبنود البضائع)
        ];
    }

    public function array(): array
    {
        return [
            // مثال 1: قيد «توريد للمكتب» إلى خزنة
            [
                'office',        // party_category
                '',              // investor_id
                7,               // status_id (مثلاً: "توريد مكتب" مرتبط بنوع inward)
                '',              // bank_account_id
                2,               // safe_id
                2500.00,         // amount
                '2025-09-01',    // transaction_date (Y-m-d)
                'توريد يومي للمكتب',
                '',              // contract_id
                '',              // installment_id
                'OFF-20250901',  // ref
                '',              // product_type_id
                '',              // quantity
            ],

            // مثال 2: «تحصيل من مستثمر» إلى حساب بنكي
            [
                'investors',     // party_category
                5,               // investor_id (مستثمر رقم 5)
                12,              // status_id (مثلاً: "تحصيل" مرتبط بنوع inward)
                1,               // bank_account_id
                '',              // safe_id
                1500.00,         // amount
                '2025-09-02',    // transaction_date
                'تحصيل أرباح المستثمر #5 لشهر 9',
                '',              // contract_id
                '',              // installment_id
                'INV5-SEP',      // ref
                '',              // product_type_id
                '',              // quantity
            ],

            // مثال 3: «شراء بضائع» (يستخدم product_type_id + quantity)
            [
                'office',
                '',              // investor_id
                20,              // status_id (مثلاً: "شراء بضائع")
                '',              // bank_account_id
                2,               // safe_id
                8000.00,         // amount (إجمالي مشتريات)
                '2025-09-03',
                'شراء بضاعة نوع #3 كمية 10',
                '',              // contract_id
                '',              // installment_id
                'BUY-0903',      // ref
                3,               // product_type_id
                10,              // quantity
            ],

            // مثال 4: «بيع بضائع»
            [
                'office',
                '',              // investor_id
                21,              // status_id (مثلاً: "بيع بضائع")
                1,               // bank_account_id
                '',              // safe_id
                12000.00,        // amount (إجمالي بيع)
                '2025-09-04',
                'بيع بضاعة نوع #4 كمية 7',
                '',              // contract_id
                '',              // installment_id
                'SELL-0904',     // ref
                4,               // product_type_id
                7,               // quantity
            ],
        ];
    }
}
