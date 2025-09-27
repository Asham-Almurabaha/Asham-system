<?php

return [
    'types' => [
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
            'confirm_delete' => 'هل أنت متأكد من حذف هذا النوع؟',
            'manage' => 'الإجراءات',
        ],
        'fields' => [
            'name' => 'اسم النوع',
            'description' => 'الوصف',
            'default_amount' => 'المبلغ الافتراضي',
            'currency_code' => 'العملة',
            'is_recurring' => 'متكرر',
            'recurrence_interval' => 'فترة التكرار',
            'created_at' => 'تاريخ الإنشاء',
            'updated_at' => 'آخر تحديث',
        ],
        'status' => [
            'recurring' => 'متكرر',
            'one_time' => 'مرة واحدة',
        ],
        'empty' => 'لم يتم إضافة أنواع مصروفات بعد.',
    ],

    'expenses' => [
        'index_title' => 'جدول المصروفات',
        'create_title' => 'جدولة مصروف',
        'edit_title' => 'تعديل المصروف',
        'actions' => [
            'create' => 'إضافة مصروف',
            'edit' => 'تعديل',
            'delete' => 'حذف',
            'save' => 'حفظ',
            'update' => 'تحديث',
            'cancel' => 'إلغاء',
            'confirm_delete' => 'هل أنت متأكد من حذف هذا المصروف؟',
            'manage' => 'الإجراءات',
        ],
        'filters' => [
            'upcoming' => 'مستحقة قريباً',
            'overdue' => 'متأخرة',
            'paid' => 'مسددة',
        ],
        'fields' => [
            'expense_type_id' => 'نوع المصروف',
            'title' => 'العنوان',
            'amount' => 'المبلغ',
            'currency_code' => 'العملة',
            'due_date' => 'تاريخ الاستحقاق',
            'paid_at' => 'تاريخ السداد',
            'notes' => 'ملاحظات',
            'reference' => 'مرجع',
            'status' => 'الحالة',
        ],
        'status_labels' => [
            'upcoming' => 'قادم',
            'overdue' => 'متأخر',
            'paid' => 'مسدد',
        ],
        'empty' => 'لا توجد مصروفات ضمن الفلتر الحالي.',
    ],

    'messages' => [
        'types' => [
            'created' => 'تم إنشاء نوع المصروف بنجاح.',
            'updated' => 'تم تحديث نوع المصروف بنجاح.',
            'deleted' => 'تم حذف نوع المصروف بنجاح.',
            'delete_blocked' => 'لا يمكن حذف النوع لارتباط مصروفات به.',
        ],
        'expenses' => [
            'created' => 'تم جدولة المصروف بنجاح.',
            'updated' => 'تم تحديث المصروف بنجاح.',
            'deleted' => 'تم حذف المصروف بنجاح.',
        ],
    ],

    'notifications' => [
        'mail' => [
            'subject' => 'تنبيه بمصروفات مستحقة (:count)',
            'greeting' => 'مرحباً،',
            'intro' => 'هناك :count من المصروفات المستحقة بتاريخ أو قبل :date ولم يتم سدادها بعد.',
            'due_on' => 'تاريخ الاستحقاق :date',
            'reference' => 'المرجع: :reference',
            'footer' => 'تم إنشاء هذا التذكير بتاريخ :datetime.',
            'salutation' => 'تحيات نظام المصروفات',
            'view_expense' => 'عرض تفاصيل المصروف',
        ],
    ],
];
