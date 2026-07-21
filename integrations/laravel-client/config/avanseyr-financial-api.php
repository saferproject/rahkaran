<?php

return [
    'base_url' => env('AVANSEYR_FINANCIAL_API_URL'),
    'client_id' => env('AVANSEYR_FINANCIAL_CLIENT_ID'),
    'client_secret' => env('AVANSEYR_FINANCIAL_CLIENT_SECRET'),

    'connect_timeout' => (int) env('AVANSEYR_FINANCIAL_CONNECT_TIMEOUT', 5),
    'timeout' => (int) env('AVANSEYR_FINANCIAL_TIMEOUT', 30),
    'verify_tls' => env('AVANSEYR_FINANCIAL_VERIFY_TLS', true),

    'cache_key' => env('AVANSEYR_FINANCIAL_TOKEN_CACHE_KEY'),
];
