<?php

return [
    'mail' => [
        'subject' => 'Open debts due (:count)',
        'greeting' => 'Hello,',
        'intro' => 'There are :count debt(s) due on or before :date that still have outstanding balances.',
        'outstanding' => 'Outstanding :amount',
        'due_on' => 'Due on :date',
        'view_debt' => 'View debt details',
        'footer' => 'This reminder was generated at :datetime.',
        'salutation' => 'Regards, Finance System',
        'unknown_name' => 'Debt #:id',
    ],
];
