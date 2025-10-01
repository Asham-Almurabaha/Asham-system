<?php

return [
    'companies' => [
        'created' => 'Company created successfully.',
        'updated' => 'Company updated successfully.',
        'deleted' => 'Company deleted successfully.',
    ],
    'transactions' => [
        'created' => 'Transaction saved successfully.',
        'updated' => 'Transaction updated successfully.',
        'deleted' => 'Transaction deleted successfully.',
        'account_required' => 'Select a bank account or a safe for this transaction.',
        'account_conflict' => 'Choose either a bank account or a safe, not both.',
        'account_amount_required' => 'Enter a valid amount for the bank or the safe.',
        'account_total_mismatch' => 'Bank and safe amounts must add up to the total amount.',
        'bank_required_for_amount' => 'Choose a bank account for the entered amount.',
        'safe_required_for_amount' => 'Choose a safe for the entered amount.',
        'single_mode_conflict' => 'Split amounts are not allowed in the standard entry mode.',
        'allocations_required' => 'Add at least one company allocation.',
        'allocation_total_mismatch' => 'The total of allocation amounts must equal the transaction total.',
    ],
    'disbursement_statuses' => [
        'created' => 'Disbursement status created successfully.',
        'updated' => 'Disbursement status updated successfully.',
        'deleted' => 'Disbursement status deleted successfully.',
    ],
];
