<?php

return [
    'zakat' => [
        'emails' => array_values(array_filter(array_map(
            static fn ($email) => is_string($email) ? trim($email) : null,
            explode(',', (string) env('ZAKAT_NOTIFICATION_EMAILS', ''))
        ))),
        'daily_at' => env('ZAKAT_NOTIFICATION_AT', '08:00'),
    ],
];
