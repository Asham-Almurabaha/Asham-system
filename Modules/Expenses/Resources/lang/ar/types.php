<?php

return [
    'index_title' => 'أنواع المصروفات',
    'create_title' => 'إضافة نوع مصروف',
    'edit_title' => 'تعديل نوع المصروف',
    'actions' => [
        'create' => 'إضافة نوع',
        'edit' => 'تعديل',
        'delete' => 'حذف',
        'save' => 'حفظ',
        'update' => 'تحديث',
        'cancel' => 'إلغاء',
        'confirm_delete' => 'هل أنت متأكد من رغبتك في حذف هذا النوع من المصروفات؟',
        'manage' => 'الإجراءات',
    ],
    'fields' => [
        'name' => 'اسم نوع المصروف',
        'description' => 'وصف النوع',
        'default_amount' => 'المبلغ الافتراضي',
        'currency_code' => 'رمز العملة',
        'is_recurring' => 'متكرر',
        'recurrence_period' => 'دورية التكرار',
        'created_at' => 'تاريخ الإنشاء',
        'updated_at' => 'آخر تحديث',
        'not_available' => 'غير متوفر',
    ],
    'status' => [
        'recurring' => 'متكرر',
        'one_time' => 'غير متكرر',
    ],
    'empty' => 'لم يتم إضافة أي أنواع للمصروفات بعد.',
];
