# Changelog

All notable changes to this project will be documented in this file.

## Unreleased

### Added

- Laravel package service provider with auto-discovery and config publishing via `tinker-auth:install`.
- Tinker command override that secures `php artisan tinker` with authentication-aware behavior.
- Tinker auth modes for sessions:
  - `strict` (authentication required)
  - `optional` (authentication can be skipped)
  - `disabled` (authentication bypassed)
- `--user|-u` support on Tinker and trait-enabled commands to prefill login identifier and force password check.
- Reusable `InteractsWithTinkerAuth` trait for custom Artisan commands with strict/optional authentication flows.
- Command-level mode attributes:
  - `#[TinkerAuthStrict]`
  - `#[TinkerAuthOptional]`
- Prompt integrations:
  - Native Laravel Prompts support when available (`text`, `password`, `suggest`)
  - Fallback to standard console input methods when prompts are unavailable
- User autocomplete support in login prompt (`autocomplete_users`, `autocomplete_limit`).
- `TinkerAuthManager` for mode resolution, guard selection, credential validation, acting-user assignment, and user suggestions.
- Support classes for user lookup and credential validation (`UserResolver`, `CredentialValidator`).
- Configuration file with environment-driven options for mode, guard, username column, max attempts, and prompt behavior.
- Test suite covering feature and unit behavior with Pest + Orchestra Testbench.

### Changed

- Composer metadata updated to reflect current package purpose and requirements.
- `laravel/tinker` is now an explicit runtime dependency.
- Documentation expanded to cover installation, configuration, mode semantics, and trait usage.

### CI / Quality

- GitHub Actions matrix tests for PHP `8.2`/`8.3` and Laravel `11`/`12`.
- CI validation for `composer validate --strict`.
- CI quality gates for static analysis (`composer analyse`) and formatting checks (`composer format:check`).
