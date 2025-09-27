<?php

return [
    'types' => [
        'index_title' => 'Expense Types',
        'create_title' => 'Create Expense Type',
        'edit_title' => 'Edit Expense Type',
        'actions' => [
            'create' => 'Add Type',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'save' => 'Save',
            'update' => 'Update',
            'cancel' => 'Cancel',
            'confirm_delete' => 'Are you sure you want to delete this expense type?',
            'manage' => 'Actions',
        ],
        'fields' => [
            'name' => 'Type Name',
            'description' => 'Description',
            'default_amount' => 'Default Amount',
            'currency_code' => 'Currency',
            'is_recurring' => 'Recurring',
            'recurrence_interval' => 'Recurrence Interval',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'not_available' => 'Not available',
        ],
        'status' => [
            'recurring' => 'Recurring',
            'one_time' => 'One-time',
        ],
        'empty' => 'No expense types yet.',
    ],

    'expenses' => [
        'index_title' => 'Expense Schedule',
        'create_title' => 'Schedule Expense',
        'edit_title' => 'Edit Expense',
        'actions' => [
            'create' => 'Add Expense',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'save' => 'Save',
            'update' => 'Update',
            'cancel' => 'Cancel',
            'confirm_delete' => 'Are you sure you want to delete this expense?',
            'manage' => 'Actions',
        ],
        'filters' => [
            'upcoming' => 'Upcoming',
            'overdue' => 'Overdue',
            'paid' => 'Paid',
        ],
        'fields' => [
            'expense_type_id' => 'Expense Type',
            'title' => 'Title',
            'amount' => 'Amount',
            'currency_code' => 'Currency',
            'due_date' => 'Due Date',
            'paid_at' => 'Paid At',
            'notes' => 'Notes',
            'reference' => 'Reference',
            'status' => 'Status',
            'not_available' => 'Not available',
        ],
        'status_labels' => [
            'upcoming' => 'Upcoming',
            'overdue' => 'Overdue',
            'paid' => 'Paid',
        ],
        'empty' => 'No expenses found for the selected filter.',
    ],

    'messages' => [
        'types' => [
            'created' => 'Expense type created successfully.',
            'updated' => 'Expense type updated successfully.',
            'deleted' => 'Expense type deleted successfully.',
            'delete_blocked' => 'Unable to delete the type because expenses are linked to it.',
        ],
        'expenses' => [
            'created' => 'Expense scheduled successfully.',
            'updated' => 'Expense updated successfully.',
            'deleted' => 'Expense deleted successfully.',
        ],
    ],

    'notifications' => [
        'mail' => [
            'subject' => 'Upcoming expenses due (:count)',
            'greeting' => 'Hello,',
            'intro' => 'There are :count expense(s) due on or before :date that still need your attention.',
            'due_on' => 'Due on :date',
            'reference' => 'Reference: :reference',
            'footer' => 'This reminder was generated at :datetime.',
            'salutation' => 'Regards, Finance System',
            'view_expense' => 'View expense details',
        ],
    ],
];
