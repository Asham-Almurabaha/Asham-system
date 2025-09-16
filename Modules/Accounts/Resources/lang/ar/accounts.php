<?php

return [
    'shared' => [
        'add' => 'إضافة',
        'edit' => 'تعديل',
        'update' => 'تحديث',
        'save' => 'حفظ',
        'cancel' => 'إلغاء',
        'delete' => 'حذف',
        'actions' => 'الإجراءات',
        'opening_balance_hint' => 'يمكنك إدخال رصيد افتتاحي لهذا الحساب عند الحاجة.',
        'currency_code_hint' => 'استخدم رمز العملة (مثل SAR).',
        'notes_hint' => 'أي ملاحظات ترغب في حفظها حول هذا السجل.',
    ],
    'status' => [
        'active' => 'نشط',
        'inactive' => 'غير نشط',
    ],
    'bank_accounts' => [
        'index_title' => 'الحسابات البنكية',
        'create_title' => 'إضافة حساب بنكي',
        'edit_title' => 'تعديل حساب بنكي',
        'actions' => [
            'create' => 'إضافة حساب بنكي',
        ],
        'fields' => [
            'name' => 'اسم الحساب',
            'bank_name' => 'اسم البنك',
            'account_number' => 'رقم الحساب',
            'iban' => 'IBAN',
            'opening_balance' => 'الرصيد الافتتاحي',
            'currency_code' => 'العملة',
            'is_active' => 'الحالة',
            'notes' => 'ملاحظات',
        ],
        'empty' => 'لا توجد حسابات بنكية.',
        'confirm_delete' => 'هل تريد حذف هذا الحساب البنكي؟',
    ],
    'safes' => [
        'index_title' => 'الخزن',
        'create_title' => 'إضافة خزنة',
        'edit_title' => 'تعديل خزنة',
        'actions' => [
            'create' => 'إضافة خزنة',
        ],
        'fields' => [
            'name' => 'اسم الخزنة',
            'location' => 'الموقع',
            'opening_balance' => 'الرصيد الافتتاحي',
            'currency_code' => 'العملة',
            'is_active' => 'الحالة',
            'notes' => 'ملاحظات',
        ],
        'empty' => 'لا توجد خزن.',
        'confirm_delete' => 'هل تريد حذف هذه الخزنة؟',
    ],
];
