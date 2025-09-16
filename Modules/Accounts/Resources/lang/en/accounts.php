<?php

return [
    'shared' => [
        'add' => 'Add',
        'edit' => 'Edit',
        'update' => 'Update',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'actions' => 'Actions',
        'opening_balance_hint' => 'Optional starting balance for this account.',
        'currency_code_hint' => 'Use the ISO currency code (e.g., SAR).',
        'notes_hint' => 'Any notes you want to keep about this record.',
    ],
    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],
    'bank_accounts' => [
        'index_title' => 'Bank Accounts',
        'create_title' => 'Add Bank Account',
        'edit_title' => 'Edit Bank Account',
        'actions' => [
            'create' => 'Add Bank Account',
        ],
        'fields' => [
            'name' => 'Account Name',
            'bank_name' => 'Bank Name',
            'account_number' => 'Account Number',
            'iban' => 'IBAN',
            'opening_balance' => 'Opening Balance',
            'currency_code' => 'Currency',
            'is_active' => 'Status',
            'notes' => 'Notes',
        ],
        'empty' => 'No bank accounts found.',
        'confirm_delete' => 'Are you sure you want to delete this bank account?',
    ],
    'safes' => [
        'index_title' => 'Safes',
        'create_title' => 'Add Safe',
        'edit_title' => 'Edit Safe',
        'actions' => [
            'create' => 'Add Safe',
        ],
        'fields' => [
            'name' => 'Safe Name',
            'location' => 'Location',
            'opening_balance' => 'Opening Balance',
            'currency_code' => 'Currency',
            'is_active' => 'Status',
            'notes' => 'Notes',
        ],
        'empty' => 'No safes found.',
        'confirm_delete' => 'Are you sure you want to delete this safe?',
    ],
];
