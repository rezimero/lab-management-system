<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notifications and critical-result escalation
    |--------------------------------------------------------------------------
    |
    | The database outbox is authoritative. In-app messages are written
    | immediately and email deliveries are attempted by a tenant-aware job.
    | SMS and WhatsApp stay explicitly unavailable until a provider is chosen.
    |
    */

    'queue_connection' => env(
        'NOTIFICATION_QUEUE_CONNECTION',
        env('QUEUE_CONNECTION', 'database')
    ),

    'email_enabled' => (bool) env(
        'NOTIFICATION_EMAIL_ENABLED',
        true
    ),

    'email_max_attempts' => max(
        1,
        (int) env('NOTIFICATION_EMAIL_MAX_ATTEMPTS', 5)
    ),

    'email_backoff_seconds' => [60, 300, 900, 3600],

    'processing_timeout_minutes' => max(
        5,
        (int) env(
            'NOTIFICATION_PROCESSING_TIMEOUT_MINUTES',
            15
        )
    ),

    'portal_frontend_url' => rtrim(
        (string) env(
            'PORTAL_FRONTEND_URL',
            'http://127.0.0.1:5173'
        ),
        '/'
    ),

    'portal_password_reset_path' => env(
        'PORTAL_PASSWORD_RESET_PATH',
        '/portal/reset-password'
    ),

    'portal_password_reset_ttl_minutes' => max(
        15,
        (int) env(
            'PORTAL_PASSWORD_RESET_TTL_MINUTES',
            60
        )
    ),

    'critical_acknowledgement_minutes' => max(
        5,
        (int) env(
            'CRITICAL_RESULT_ACKNOWLEDGEMENT_MINUTES',
            15
        )
    ),

    'critical_repeat_minutes' => max(
        5,
        (int) env(
            'CRITICAL_RESULT_REPEAT_MINUTES',
            15
        )
    ),

    'sms' => [
        'enabled' => false,
        'reason' => 'No reviewed SMS provider is configured.',
    ],

    'whatsapp' => [
        'enabled' => false,
        'reason' => 'No reviewed WhatsApp provider is configured.',
    ],
];
