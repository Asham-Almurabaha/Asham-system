<?php

return [
    'title' => [
        'create' => 'تسجيل سداد مصروف',
    ],
    'breadcrumb' => [
        'index' => 'جدولة المصروفات',
        'payments' => 'السدادات',
    ],
    'summary' => [
        'heading' => 'بيانات المصروف',
        'amount' => 'المبلغ المجدول',
        'paid' => 'المسدد',
        'outstanding' => 'المتبقي',
        'due_date' => 'تاريخ الاستحقاق',
    ],
    'form' => [
        'heading' => 'إضافة سداد',
        'submit' => 'تسجيل السداد',
        'cancel' => 'الرجوع إلى المصروفات',
    ],
    'fields' => [
        'amount' => 'المبلغ',
        'paid_at' => 'تاريخ السداد',
        'bank_account_id' => 'الحساب البنكي',
        'safe_id' => 'الخزانة',
        'account_choice' => 'مصدر السداد',
        'notes' => 'ملاحظات',
        'account_placeholder' => 'اختر حسابًا بنكيًا أو خزانة',
        'safe_placeholder' => 'اختر خزانة',
    ],
    'groups' => [
        'banks' => 'الحسابات البنكية',
        'safes' => 'الخزائن',
    ],
    'history' => [
        'heading' => 'سجل السدادات',
        'empty' => 'لا توجد سدادات مسجلة بعد.',
        'table' => [
            'paid_at' => 'تاريخ السداد',
            'amount' => 'المبلغ',
            'account' => 'الحساب',
            'notes' => 'ملاحظات',
        ],
    ],
    'alerts' => [
        'settled' => 'تم سداد هذا المصروف بالكامل، لا يمكن إضافة سدادات جديدة.',
    ],
    'hints' => [
        'account_choice' => 'اختر حسابًا بنكيًا أو خزانة لتسجيل السداد.',
        'account_available' => 'المتاح في الحساب:',
        'account_availability' => 'الرصيد المتاح في الحساب',
    ],
    'placeholders' => [
        'notes' => 'أدخل ملاحظات إضافية (اختياري)',
    ],
    'validation' => [
        'amount_exceeds_available' => 'المبلغ يتجاوز الرصيد المتاح في الحساب (:available).',
    ],
    'ledger' => [
        'notes' => 'سداد مصروف رقم :id (:title)',
    ],
];
