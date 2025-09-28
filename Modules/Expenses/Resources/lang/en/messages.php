<?php

return [
    'types' => [
        'created' => 'Expense type created successfully.',
        'updated' => 'Expense type updated successfully.',
        'deleted' => 'Expense type deleted successfully.',
        'delete_blocked' => 'Unable to delete the type because expenses are linked to it.',
    ],
    'recurrence_periods' => [
        'created' => 'Recurrence period created successfully.',
        'updated' => 'Recurrence period updated successfully.',
        'deleted' => 'Recurrence period deleted successfully.',
        'delete_blocked' => 'Unable to delete the period because expense types are linked to it.',
        'protected_edit' => 'This period is protected and cannot be edited.',
        'protected_delete' => 'This period is protected and cannot be deleted.',
    ],
    'expenses' => [
        'created' => 'Expense scheduled successfully.',
        'updated' => 'Expense updated successfully.',
        'deleted' => 'Expense deleted successfully.',
    ],
    'payments' => [
        'created' => 'Expense payment recorded successfully.',
    ],
    'validation' => [
        'account_required' => 'Please select a bank account or a safe.',
        'account_conflict' => 'Please select either a bank account or a safe, not both.',
    ],
];
