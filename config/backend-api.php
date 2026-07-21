<?php

return [
    'access_token_ttl' => (int) env('BACKEND_API_ACCESS_TOKEN_TTL', 15),
    'refresh_token_ttl' => (int) env('BACKEND_API_REFRESH_TOKEN_TTL', 43200),

    'default_abilities' => [
        'vouchers:create',
        'dls:create',
        'parties:create',
    ],
];
