# Tinker Auth

Laravel package scaffold for `joke2k/tinker-auth`.

## Requirements

- PHP 8.2+
- Composer
- Docker + Docker Compose (for containerized tests)

## Local Installation

```bash
composer install
```

## Run Tests

Local:

```bash
composer test
```

With Docker:

```bash
docker compose run --rm package-test
```

Or via Composer script:

```bash
composer test:docker
```

## Structure

- `src/TinkerAuthServiceProvider.php`: package service provider.
- `src/Commands/InstallCommand.php`: `tinker-auth:install` command.
- `config/tinker-auth.php`: publishable configuration.
- `database/migrations/*.stub`: publishable migration stub.
- `routes/web.php`: demo route (`/tinker-auth/health`).
- `tests/`: Pest + Orchestra Testbench tests.
