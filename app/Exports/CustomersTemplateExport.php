<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomersTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        // أعمدة متوافقة مع جدول customers والاستيراد
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
                'أحمد علي',     // name
                '1000000001',    // national_id
                '0555555555',    // phone (ستتحول تلقائياً إلى 9665XXXXXXXX)
                'ahmad@example.com',
                'الرياض - حي النرجس',
                'سعودي',         // nationality (اسم موجود في جدول nationalities)
                'حكومي',         // title (اسم موجود في جدول titles)
                'عميل قديم – أولوية تواصل',
                '',              // id_card_image
            ],
            // مثال 2 (إنجليزي)
            [
                'Sara',
                '2000000002',
                '+966512345678',
                'sara@example.com',
                'Dammam - Corniche',
                'سعودي',
                'حكومي',
                'ملاحظات إضافية',
                '',
            ],
        ];
    }
}
