<?php

return [
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
];
