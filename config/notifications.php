<?php

return [
    'header' => [
        'cache_ttl_seconds' => (int) env('HEADER_NOTIFICATIONS_CACHE_TTL', 60),
    ],
    'zakat' => [
        'emails' => array_values(array_filter(array_map(
            static fn ($email) => is_string($email) ? trim($email) : null,
            explode(',', (string) env('ZAKAT_NOTIFICATION_EMAILS', ''))
        ))),
        'daily_at' => env('ZAKAT_NOTIFICATION_AT', '08:00'),
    ],
    'debts' => [
        'emails' => array_values(array_filter(array_map(
            static fn ($email) => is_string($email) ? trim($email) : null,
            explode(',', (string) env('DEBTS_NOTIFICATION_EMAILS', ''))
        ))),
        'daily_at' => env('DEBTS_NOTIFICATION_AT', '08:00'),
    ],
];
