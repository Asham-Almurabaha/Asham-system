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
            'confirm_delete' => 'هل أنت متأكد من رغبتك في حذف هذا النوع من المصروفات؟',
            'manage' => 'الإجراءات',
        ],
        'fields' => [
            'name' => 'اسم نوع المصروف',
            'description' => 'وصف النوع',
            'default_amount' => 'المبلغ الافتراضي',
            'currency_code' => 'رمز العملة',
            'is_recurring' => 'متكرر',
            'recurrence_interval' => 'فترة التكرار',
            'created_at' => 'تاريخ الإنشاء',
            'updated_at' => 'آخر تحديث',
            'not_available' => 'غير متوفر',
        ],
        'status' => [
            'recurring' => 'متكرر',
            'one_time' => 'غير متكرر',
        ],
        'empty' => 'لم يتم إضافة أي أنواع للمصروفات بعد.',
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
            'confirm_delete' => 'هل أنت متأكد من رغبتك في حذف هذا المصروف؟',
            'manage' => 'الإجراءات',
        ],
        'filters' => [
            'upcoming' => 'مستحقة قريباً',
            'overdue' => 'متأخرة',
            'paid' => 'مسددة',
        ],
        'fields' => [
            'expense_type_id' => 'نوع المصروف',
            'title' => 'عنوان المصروف',
            'amount' => 'المبلغ',
            'currency_code' => 'رمز العملة',
            'due_date' => 'تاريخ الاستحقاق',
            'paid_at' => 'تاريخ السداد',
            'notes' => 'ملاحظات إضافية',
            'reference' => 'المرجع',
            'status' => 'الحالة',
            'not_available' => 'غير متوفر',
        ],
        'status_labels' => [
            'upcoming' => 'مستحقة قريباً',
            'overdue' => 'متأخرة',
            'paid' => 'مسددة',
        ],
        'empty' => 'لا توجد مصروفات ضمن الفلتر الحالي.',
    ],

    'messages' => [
        'types' => [
            'created' => 'تم إنشاء نوع المصروف بنجاح.',
            'updated' => 'تم تحديث نوع المصروف بنجاح.',
            'deleted' => 'تم حذف نوع المصروف بنجاح.',
            'delete_blocked' => 'لا يمكن حذف هذا النوع لارتباط مصروفات به.',
        ],
        'expenses' => [
            'created' => 'تمت جدولة المصروف بنجاح.',
            'updated' => 'تم تحديث المصروف بنجاح.',
            'deleted' => 'تم حذف المصروف بنجاح.',
        ],
    ],

    'notifications' => [
        'mail' => [
            'subject' => 'تنبيه بمصروفات مستحقة (:count)',
            'greeting' => 'مرحباً،',
            'intro' => 'يوجد :count من المصروفات المستحقة بتاريخ أو قبل :date وما زالت بحاجة إلى اهتمامك.',
            'due_on' => 'مستحق في :date',
            'reference' => 'المرجع: :reference',
            'footer' => 'تم إنشاء هذا التذكير في :datetime.',
            'salutation' => 'مع تحيات نظام المصروفات',
            'view_expense' => 'عرض تفاصيل المصروف',
        ],
    ],
];
