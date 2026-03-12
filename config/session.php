<?php

declare(strict_types=1);

return [
    'driver' => env('SESSION_DRIVER', 'auto'),
    'name' => env('SESSION_NAME', '3ymen_session'),
    'lifetime' => (int) env('SESSION_LIFETIME', 120),
    'path' => storage_path('sessions'),
    'cookie_path' => '/',
    'cookie_domain' => null,
    'cookie_secure' => false,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
];
