<?php

return [
    'title' => [
        'create' => 'Record Expense Payment',
    ],
    'breadcrumb' => [
        'index' => 'Expense Schedule',
        'payments' => 'Payments',
    ],
    'summary' => [
        'heading' => 'Expense details',
        'amount' => 'Scheduled amount',
        'paid' => 'Paid amount',
        'outstanding' => 'Outstanding amount',
        'due_date' => 'Due date',
    ],
    'form' => [
        'heading' => 'Record payment',
        'submit' => 'Record payment',
        'cancel' => 'Back to expenses',
    ],
    'fields' => [
        'amount' => 'Amount',
        'paid_at' => 'Payment date',
        'bank_account_id' => 'Bank account',
        'safe_id' => 'Safe',
        'notes' => 'Notes',
        'account_placeholder' => 'Select a bank account',
        'safe_placeholder' => 'Select a safe',
    ],
    'history' => [
        'heading' => 'Payment history',
        'empty' => 'No payments recorded yet.',
        'table' => [
            'paid_at' => 'Paid at',
            'amount' => 'Amount',
            'account' => 'Account',
            'notes' => 'Notes',
        ],
    ],
    'alerts' => [
        'settled' => 'This expense is fully paid. New payments are disabled.',
    ],
    'ledger' => [
        'notes' => 'Expense payment #:id (:title)',
    ],
];
