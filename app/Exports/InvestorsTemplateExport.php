<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InvestorsTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'name',                    // إلزامي
            'national_id',             // إلزامي (10 أرقام يبدأ بـ 1 أو 2)
            'phone',                   // إلزامي (05XXXXXXXX أو 9665XXXXXXXX)
            'email',                   // اختياري
            'address',                 // اختياري
            'nationality',             // اختياري (بالاسم ويجب أن يكون موجوداً بجدول nationalities)
            'title',                   // اختياري (بالاسم ويجب أن يكون موجوداً بجدول titles)
            'office_share_percentage', // اختياري (رقم عشري % — مثال: 10 أو 12.5)
            'notes',                   // اختياري
            'id_card_image',           // اختياري (مسار/اسم ملف)
            'contract_image',          // اختياري (مسار/اسم ملف)
        ];
    }

    public function array(): array
    {
        return [
            [
                'مستثمر تجريبي',
                '1000000005',
                '0555555555',
                'investor@example.com',
                'الرياض',
                'سعودي',
                'رجل أعمال',
                '10',
                'ملاحظات',
                '',
                '',
            ],
            [
                'Sara',
                '2000000006',
                '+966512345678',
                'sara.inv@example.com',
                'Dammam',
                'سعودي',
                'حكومي',
                '12.5',
                '',
                '',
                '',
            ],
        ];
    }
}
