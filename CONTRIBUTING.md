# Contributing

Thanks for contributing to `joke2k/tinker-auth`.

## Local Setup

1. Fork and clone the repository.
2. Install dependencies:

```bash
composer install
```

3. Run the quality checks before opening a pull request:

```bash
composer test
composer analyse
composer format:check
composer rector:dry
```

Use these fix commands locally when needed:

```bash
composer format
composer rector
```

## Coding Standards

- Follow `.editorconfig` (UTF-8, LF, 4-space indentation, trimmed trailing whitespace).
- Use strict types (`declare(strict_types=1);`) in PHP files.
- Keep namespaces aligned with PSR-4 autoloading:
  - `src/` -> `Joke2k\\TinkerAuth\\`
  - `tests/` -> `Joke2k\\TinkerAuth\\Tests\\`
- Use Laravel Pint rules from `pint.json`.
- Keep class names in `StudlyCase`, methods/properties in `camelCase`.

## Testing and Quality Expectations

- Place integration/behavior tests in `tests/Feature`.
- Place isolated logic tests in `tests/Unit`.
- Use descriptive test names that communicate behavior.
- Validate auth-related changes across `strict`, `optional`, and `disabled` modes.
- For coverage runs, use:

```bash
composer test:coverage
```

## Commit Guidelines

- Use conventional prefixes used in this repository:
  - `fix:`
  - `test:`
  - `docs:`
  - `style:`
  - `chore:`
- Keep commits focused and atomic.
- Write commit summaries in imperative mood (example: `fix: clear stale auth errors`).

## Pull Request Expectations

- Describe the purpose and scope of the change.
- Summarize key implementation details and tradeoffs.
- Include executed command results (`composer test`, `composer analyse`, `composer format:check` at minimum).
- Link related issues when applicable.
- Update docs (`README.md`) and changelog (`CHANGELOG.md`) when behavior or public API changes.

## Release Etiquette

- Use `CHANGELOG.md` as the source of release notes.
- Add user-facing changes under `## Unreleased` in the correct section (`Added`, `Changed`, etc.).
- Keep entries concise and behavior-focused.
- Before cutting a release, ensure CI is green and local checks pass:

```bash
composer test
composer analyse
composer format:check
```

- Do not include secrets or hardcoded credentials in code or tests.
