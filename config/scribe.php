<?php

return [
    'base_url' => env('APP_URL', 'http://127.0.0.1:8000'),
    'auth' => [
        'enabled' => true,
        'in' => 'basic',
        'name' => 'Authorization',
        'use_value' => 'Basic {username}:{password}',
    ],
    'servers' => [
        ['url' => env('APP_URL', 'http://127.0.0.1:8000')],
    ],
]; 