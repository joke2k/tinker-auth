# Tinker Auth

[![Latest Version on Packagist](https://img.shields.io/packagist/v/joke2k/tinker-auth.svg?style=flat-square)](https://packagist.org/packages/joke2k/tinker-auth)
[![Total Downloads](https://img.shields.io/packagist/dt/joke2k/tinker-auth.svg?style=flat-square)](https://packagist.org/packages/joke2k/tinker-auth)
[![License](https://img.shields.io/packagist/l/joke2k/tinker-auth.svg?style=flat-square)](https://packagist.org/packages/joke2k/tinker-auth)

Tinker Auth is a Laravel package that enforces or enables user authentication for `php artisan tinker` sessions and provides a reusable command trait for command-level user context.

## Features

- Tinker session auth modes:
  - `strict`: authentication is required.
  - `optional`: authentication is available but can be skipped.
  - `disabled`: no authentication is performed.
- Tinker command supports `--user|-u` to prefill the login username.
- Interactive auth prompts use `laravel/prompts` when available for improved terminal UX.
- Per-environment behavior through `.env` values.
- Authenticated Tinker session sets the active Laravel user (`Auth::user()`).
- Reusable command trait that adds:
  - `--user|-u`: prefill login username and require password.
  - Per-command auth mode (`strict|optional`) via class attribute.

## Requirements

- PHP 8.2+
- Laravel 11 or 12
- `illuminate/support`
- `laravel/tinker`
- `laravel/prompts` (optional)

## Installation

1. Install the package:

```bash
composer require joke2k/tinker-auth
```

2. Publish package configuration:

```bash
php artisan tinker-auth:install
```

Manual alternative:

```bash
php artisan vendor:publish --provider="Joke2k\TinkerAuth\TinkerAuthServiceProvider" --tag="tinker-auth-config"
```

3. Set your `.env` values (example):

```env
TINKER_AUTH_MODE=optional
TINKER_AUTH_USERNAME_COLUMN=email
TINKER_AUTH_GUARD=web
TINKER_AUTH_MAX_ATTEMPTS=3
```

4. Run Tinker with authentication support:

```bash
# normal tinker flow (uses TINKER_AUTH_MODE)
php artisan tinker

# prefill login with --user / -u and prompt for password
php artisan tinker --user=admin@example.com
php artisan tinker -u admin@example.com
```

Optional (recommended) for better interactive prompts:

```bash
composer require laravel/prompts
```

## Configuration

Published config: `config/tinker-auth.php`

```php
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
```

Example per-environment overrides:

```env
# .env.local
TINKER_AUTH_MODE=optional
TINKER_AUTH_AUTOCOMPLETE_USERS=true

# .env.production
TINKER_AUTH_MODE=strict
TINKER_AUTH_AUTOCOMPLETE_USERS=false
```

## Tinker Behavior

- `TINKER_AUTH_MODE=strict`
  - Interactive: prompts for login + password.
  - Non-interactive: exits with failure.
- `TINKER_AUTH_MODE=optional`
  - Interactive: allows authentication or skip.
  - Non-interactive: continues without auth.
- `TINKER_AUTH_MODE=disabled`
  - Always continues without auth.
- `--user|-u <identifier>` (on `php artisan tinker`)
  - Uses the identifier as login username.
  - Always prompts for password.
  - Behaves as strict authentication for that run.
- `$_u` context variable
  - Contains the authenticated user for the current Tinker session.
  - Is `null` when no user is authenticated.

## Command Trait Usage

Use the trait in any custom Artisan command:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Joke2k\TinkerAuth\Attributes\TinkerAuthOptional;
use Joke2k\TinkerAuth\Concerns\InteractsWithTinkerAuth;

#[TinkerAuthOptional]
class RebuildSearchIndex extends Command
{
    use InteractsWithTinkerAuth;

    protected $signature = 'search:rebuild';

    public function handle(): int
    {
        $this->info('Running as '.(auth()->user()?->email ?? 'guest'));

        return self::SUCCESS;
    }
}
```

Available options:

- `--user|-u` user identifier (matching `username_column`), then password is prompted.

Command mode resolution:

- If command has `#[TinkerAuthStrict]`, strict mode is used.
- If command has `#[TinkerAuthOptional]`, optional mode is used.
- Otherwise package falls back to `tinker-auth.command_trait.default_mode`.

## Testing

Install dependencies including dev packages before running checks:

```bash
composer install
```

Run the automated checks:

```bash
composer test
composer analyse
composer format:check
composer rector:dry
```

Useful local maintenance commands:

```bash
composer format
composer rector
```

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for local setup, coding standards, testing requirements, and PR expectations.
