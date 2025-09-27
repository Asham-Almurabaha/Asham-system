<?php

return [
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
];
