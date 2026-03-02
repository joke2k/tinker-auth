<?php

declare(strict_types=1);

return [
    'enabled' => env('TINKER_AUTH_ENABLED', true),

    'middleware' => [
        'web',
    ],

    'route' => [
        'prefix' => env('TINKER_AUTH_ROUTE_PREFIX', 'tinker-auth'),
        'name' => 'tinker-auth.',
    ],
];
