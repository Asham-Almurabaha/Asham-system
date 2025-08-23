<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GuarantorsTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        // أعمدة متوافقة مع جدول guarantors والاستيراد
        return [
            'name',            // إلزامي
            'national_id',     // إلزامي (10 أرقام يبدأ بـ 1 أو 2)
            'phone',           // إلزامي (05XXXXXXXX أو 9665XXXXXXXX)
            'email',           // اختياري
            'address',         // اختياري
            'nationality',     // اختياري (بالاسم ويجب يكون موجود بجدول nationalities)
            'title',           // اختياري (بالاسم ويجب يكون موجود بجدول titles)
            'notes',           // اختياري
            'id_card_image',   // اختياري (مسار/اسم ملف إن وُجد)
        ];
    }

    public function array(): array
    {
        return [
            // مثال 1 (عربي)
            [
                'محمد الضامن',   // name
                '1000000003',    // national_id
                '0550000000',    // phone
                'mohd@example.com',
                'الرياض - حي الملقا',
                'سعودي',         // nationality (اسم موجود في جدول nationalities)
                'موظف',          // title (اسم موجود في جدول titles)
                'ضامن للعميل أحمد',
                '',
            ],
            // مثال 2 (إنجليزي)
            [
                'Khaled',
                '2000000004',
                '+966512312345',
                'khaled@example.com',
                'Dammam',
                'سعودي',
                'حكومي',
                'ملاحظات إضافية',
                '',
            ],
        ];
    }
}
