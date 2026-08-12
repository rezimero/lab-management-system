<?php

return [
    /*
    |--------------------------------------------------------------------------
    | External Portal Authentication
    |--------------------------------------------------------------------------
    |
    | Patient and clinician portal tokens are deliberately separate from the
    | internal Sanctum tenant-user token boundary.
    |
    */

    'access_token_ttl_minutes' => (int) env(
        'PORTAL_ACCESS_TOKEN_TTL_MINUTES',
        720
    ),

    'invitation_ttl_minutes' => (int) env(
        'PORTAL_INVITATION_TTL_MINUTES',
        1440
    ),

    'activation_path' => env(
        'PORTAL_ACTIVATION_PATH',
        '/portal/activate'
    ),
];
