<?php

return [
    'companies' => [
        'created' => 'تم إنشاء الشركة بنجاح.',
        'updated' => 'تم تحديث بيانات الشركة بنجاح.',
        'deleted' => 'تم حذف الشركة بنجاح.',
    ],
    'transactions' => [
        'created' => 'تم حفظ العملية بنجاح.',
        'updated' => 'تم تحديث العملية بنجاح.',
        'deleted' => 'تم حذف العملية بنجاح.',
        'account_required' => 'يرجى اختيار حساب بنكي أو خزينة للعملية.',
        'account_conflict' => 'اختر حساباً بنكياً أو خزينة واحدة فقط.',
        'account_amount_required' => 'أدخل قيمة صالحة في الحساب البنكي أو الخزينة.',
        'account_total_mismatch' => 'يجب أن يساوي مجموع البنك والخزنة إجمالي المبلغ.',
        'bank_required_for_amount' => 'يرجى اختيار الحساب البنكي للمبلغ المحدد.',
        'safe_required_for_amount' => 'يرجى اختيار الخزينة للمبلغ المحدد.',
        'single_mode_conflict' => 'لا يمكن تقسيم القيد في وضع القيد العادي.',
        'allocations_required' => 'أضف توزيعاً واحداً على الأقل للشركات.',
        'allocation_total_mismatch' => 'يجب أن يساوي مجموع التوزيعات إجمالي مبلغ العملية.',
        'availability_fetch_error' => 'تعذّر الحصول على المتاح للحساب.',
    ],
    'disbursement_statuses' => [
        'created' => 'تم إنشاء حالة الصرف بنجاح.',
        'updated' => 'تم تحديث حالة الصرف بنجاح.',
        'deleted' => 'تم حذف حالة الصرف بنجاح.',
    ],
];
