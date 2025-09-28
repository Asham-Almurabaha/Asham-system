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
        'notes' => 'ملاحظات',
        'account_placeholder' => 'اختر حسابًا بنكيًا',
        'safe_placeholder' => 'اختر خزانة',
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
    ],
    'placeholders' => [
        'notes' => 'أدخل ملاحظات إضافية (اختياري)',
    ],
    'ledger' => [
        'notes' => 'سداد مصروف رقم :id (:title)',
    ],
];
