<?php

declare(strict_types=1);

namespace Joke2k\TinkerAuth\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use InvalidArgumentException;
use Joke2k\TinkerAuth\Attributes\TinkerAuthOptional;
use Joke2k\TinkerAuth\Attributes\TinkerAuthStrict;
use Joke2k\TinkerAuth\TinkerAuthManager;
use ReflectionClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

trait InteractsWithTinkerAuth
{
    /**
     * Return the currently authenticated user for the configured tinker guard.
     *
     * Example:
     * ```php
     * $email = $this->tinkerAuthUser()?->email ?? 'guest';
     * $this->line("Running as {$email}");
     * ```
     */
    protected function tinkerAuthUser(): ?Authenticatable
    {
        return app(TinkerAuthManager::class)->guard()->user();
    }

    /**
     * Add the `--user` option to commands that use this trait.
     *
     * Example:
     * ```bash
     * php artisan app:example --user=admin@example.com
     * ```
     */
    protected function configure(): void
    {
        parent::configure();

        $definition = $this->getDefinition();

        if (! $definition->hasOption('user')) {
            $definition->addOption(new InputOption(
                'user',
                'u',
                InputOption::VALUE_OPTIONAL,
                'Use this user identifier as login and prompt for password'
            ));
        }
    }

    /**
     * Bootstrap tinker auth during command initialization.
     *
     * Invalid auth mode values (for example an unsupported `tinkerAuthMode`) are converted
     * into a command failure message.
     *
     * Example:
     * ```php
     * protected function initialize(InputInterface $input, OutputInterface $output): void
     * {
     *     parent::initialize($input, $output);
     *     $this->line('Auth initialization already executed by the trait.');
     * }
     * ```
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        try {
            $this->initializeTinkerAuth($input, $output);
        } catch (InvalidArgumentException $invalidArgumentException) {
            $this->fail($invalidArgumentException->getMessage());
        }
    }

    /**
     * Resolve the command auth mode and authenticate the current execution context.
     *
     * Behavior examples:
     * - Existing guard user: returns that user immediately.
     * - `disabled` mode: returns `null` without prompting.
     * - `--user` provided: prompts for password once for that identifier.
     * - Non-interactive `strict` mode without `--user`: fails.
     * - Interactive prompt loop: retries up to `tinker-auth.max_attempts`.
     *
     * Example:
     * ```php
     * $user = $this->initializeTinkerAuth($input, $output);
     * $this->line($user ? 'Authenticated' : 'Continuing as guest');
     * ```
     */
    protected function initializeTinkerAuth(InputInterface $input, OutputInterface $output): ?Authenticatable
    {
        $manager = app(TinkerAuthManager::class);

        if ($manager->guard()->user() !== null) {
            return $manager->guard()->user();
        }

        $mode = $this->resolveEffectiveTinkerAuthMode($manager);

        if ($mode === 'disabled') {
            return null;
        }

        $identifier = $this->normalizeInputOption($input->getOption('user'));

        if ($identifier !== null) {
            $user = $this->authenticateByCredentials($manager, $identifier);

            if (! $user instanceof Authenticatable) {
                $this->fail('Invalid credentials for the provided user.');
            }

            $manager->setActingUser($user);
            $this->onTinkerAuthSuccess($output);

            return $user;
        }

        if (! $input->isInteractive()) {
            if ($mode === 'strict') {
                $this->fail($this->strictNonInteractiveAuthMessage());
            }

            return null;
        }

        $this->onTinkerAuthPromptStart($mode, $output);

        $attempts = max(1, (int) config('tinker-auth.max_attempts', 3));

        for ($i = 1; $i <= $attempts; $i++) {
            $identifier = $this->promptTinkerAuthLogin($mode);

            if ($identifier === '') {
                if ($mode === 'optional') {
                    return null;
                }

                $this->reportTinkerAuthError('A login value is required in strict mode.');

                continue;
            }

            $user = $this->authenticateByCredentials($manager, $identifier);

            if ($user instanceof Authenticatable) {
                $manager->setActingUser($user);
                $this->onTinkerAuthSuccess($output);

                return $user;
            }

            $this->reportTinkerAuthError('Invalid credentials.');
        }

        if ($mode === 'strict') {
            $this->fail('Unable to authenticate this session.');
        }

        return null;
    }

    /**
     * Return the final auth mode used by this command (`strict`, `optional`, or `disabled`).
     *
     * The manager combines command-level mode hints with package configuration defaults.
     *
     * Example:
     * ```php
     * $mode = $this->resolveEffectiveTinkerAuthMode(app(TinkerAuthManager::class));
     * $this->line("Effective auth mode: {$mode}");
     * ```
     */
    protected function resolveEffectiveTinkerAuthMode(TinkerAuthManager $manager): string
    {
        return $manager->resolveCommandMode($this->resolveTinkerAuthMode());
    }

    /**
     * Resolve command-level auth mode hints declared in the command class.
     *
     * Resolution order:
     * - `#[TinkerAuthStrict]` / `#[TinkerAuthOptional]` attributes.
     * - `tinkerAuthMode()` method.
     * - `$tinkerAuthMode` property.
     * - `null` (fall back to package default).
     *
     * Example using an attribute:
     * ```php
     * #[TinkerAuthStrict]
     * class AuditCommand extends Command
     * {
     *     use InteractsWithTinkerAuth;
     * }
     * ```
     */
    protected function resolveTinkerAuthMode(): ?string
    {
        $reflection = new ReflectionClass($this);
        $strict = $reflection->getAttributes(TinkerAuthStrict::class) !== [];
        $optional = $reflection->getAttributes(TinkerAuthOptional::class) !== [];

        if ($strict && $optional) {
            $this->fail('Command cannot declare both TinkerAuthStrict and TinkerAuthOptional.');
        }

        if ($strict || $optional) {
            $mode = $strict ? 'strict' : 'optional';

            if ($reflection->hasProperty('tinkerAuthMode')) {
                $reflection->getProperty('tinkerAuthMode')->setValue($this, $mode);
            }

            return $mode;
        }

        if ($reflection->hasMethod('tinkerAuthMode')) {
            /** @var mixed $mode */
            $mode = $reflection->getMethod('tinkerAuthMode')->invoke($this);

            return is_string($mode) ? $mode : null;
        }

        if ($reflection->hasProperty('tinkerAuthMode')) {
            /** @var mixed $mode */
            $mode = $reflection->getProperty('tinkerAuthMode')->getValue($this);

            return is_string($mode) ? $mode : null;
        }

        return null;
    }

    /**
     * Hook called before interactive credential prompts are shown.
     *
     * Override this in a command to provide mode-specific prompt context.
     *
     * Example:
     * ```php
     * protected function onTinkerAuthPromptStart(string $mode, OutputInterface $output): void
     * {
     *     $this->info($mode === 'strict'
     *         ? 'Authentication required for this command.'
     *         : 'Press enter to continue as guest.');
     * }
     * ```
     */
    protected function onTinkerAuthPromptStart(string $mode, OutputInterface $output): void
    {
        // Hook for command-specific prompt messaging.
    }

    /**
     * Hook called after successful authentication.
     *
     * Example:
     * ```php
     * protected function onTinkerAuthSuccess(OutputInterface $output): void
     * {
     *     $this->info('Authenticated as '.$this->tinkerAuthUser()?->email);
     * }
     * ```
     */
    protected function onTinkerAuthSuccess(OutputInterface $output): void
    {
        // Hook for command-specific success messaging.
    }

    /**
     * Message shown when strict mode cannot prompt in non-interactive execution.
     *
     * Override to provide command-specific guidance.
     *
     * Example:
     * ```php
     * protected function strictNonInteractiveAuthMessage(): string
     * {
     *     return 'CI runs must pass --user=<login> for strict auth.';
     * }
     * ```
     */
    protected function strictNonInteractiveAuthMessage(): string
    {
        return 'Tinker Auth strict mode requires --user when the command is non-interactive.';
    }

    /**
     * Report an authentication error to the console when possible.
     *
     * This method safely checks for an `error()` method before calling it.
     *
     * Example:
     * ```php
     * $this->reportTinkerAuthError('Invalid credentials.');
     * ```
     */
    protected function reportTinkerAuthError(string $message): void
    {
        if (method_exists($this, 'error')) {
            $this->error($message);
        }
    }

    /**
     * Prompt for a password using Laravel Prompts when available, otherwise `secret()`.
     *
     * Example:
     * ```php
     * $password = $this->promptTinkerAuthPassword();
     * ```
     */
    protected function promptTinkerAuthPassword(): string
    {
        if (function_exists('Laravel\\Prompts\\password')) {
            return \Laravel\Prompts\password(
                label: (string) config('tinker-auth.prompt.password_label', 'Password'),
                required: true,
            );
        }

        return (string) $this->secret((string) config('tinker-auth.prompt.password_label', 'Password'));
    }

    /**
     * Prompt for login identifier based on mode and prompt capabilities.
     *
     * Behavior examples:
     * - `strict` mode: input is required.
     * - `optional` mode: empty input is allowed.
     * - Autocomplete enabled: uses `suggest()` with user identifier options.
     * - Fallback: uses `text()` or command `ask()`.
     *
     * Example:
     * ```php
     * $identifier = $this->promptTinkerAuthLogin('optional');
     * ```
     */
    protected function promptTinkerAuthLogin(string $mode): string
    {
        $required = $mode === 'strict';

        if ((bool) config('tinker-auth.prompt.autocomplete_users', false)
            && function_exists('Laravel\\Prompts\\suggest')
        ) {
            $options = app(TinkerAuthManager::class)->suggestUserIdentifiers();

            if ($options !== []) {
                return trim(\Laravel\Prompts\suggest(
                    label: (string) config('tinker-auth.prompt.login_label', 'Login'),
                    options: $options,
                    required: $required,
                ));
            }
        }

        if (function_exists('Laravel\\Prompts\\text')) {
            return trim(\Laravel\Prompts\text(
                label: (string) config('tinker-auth.prompt.login_label', 'Login'),
                required: $required,
            ));
        }

        return trim((string) $this->ask((string) config('tinker-auth.prompt.login_label', 'Login')));
    }

    private function authenticateByCredentials(TinkerAuthManager $manager, string $identifier): ?Authenticatable
    {
        $password = $this->promptTinkerAuthPassword();

        return $manager->attemptLogin($identifier, $password);
    }

    private function normalizeInputOption(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
