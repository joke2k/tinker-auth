<?php

declare(strict_types=1);

return [
    'mode' => env('TINKER_AUTH_MODE', 'optional'),

    'username_column' => env('TINKER_AUTH_USERNAME_COLUMN', 'email'),

    'guard' => env('TINKER_AUTH_GUARD'),

    'max_attempts' => (int) env('TINKER_AUTH_MAX_ATTEMPTS', 3),

    'prompt' => [
        'login_label' => 'Login',
        'password_label' => 'Password',
        'strict_message' => 'Authentication is required to start Tinker in strict mode.',
        'optional_message' => 'Press enter to continue without authentication.',
        'autocomplete_users' => (bool) env('TINKER_AUTH_AUTOCOMPLETE_USERS', false),
        'autocomplete_limit' => (int) env('TINKER_AUTH_AUTOCOMPLETE_LIMIT', 5),
    ],

    'command_trait' => [
        'default_mode' => env('TINKER_AUTH_COMMAND_DEFAULT_MODE', 'strict'),
    ],
];
