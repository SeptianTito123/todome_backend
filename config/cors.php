<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'], // Izinkan semua Method
    'allowed_origins' => ['*'], // PENTING: Izinkan semua domain (termasuk localhost web)
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'], // PENTING: Izinkan semua header
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];  