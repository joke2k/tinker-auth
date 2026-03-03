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
    protected function tinkerAuthUser(): ?Authenticatable
    {
        return app(TinkerAuthManager::class)->guard()->user();
    }

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

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        try {
            $this->initializeTinkerAuth($input, $output);
        } catch (InvalidArgumentException $invalidArgumentException) {
            $this->fail($invalidArgumentException->getMessage());
        }
    }

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

    protected function resolveEffectiveTinkerAuthMode(TinkerAuthManager $manager): string
    {
        return $manager->resolveCommandMode($this->resolveTinkerAuthMode());
    }

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

    protected function onTinkerAuthPromptStart(string $mode, OutputInterface $output): void
    {
        // Hook for command-specific prompt messaging.
    }

    protected function onTinkerAuthSuccess(OutputInterface $output): void
    {
        // Hook for command-specific success messaging.
    }

    protected function strictNonInteractiveAuthMessage(): string
    {
        return 'Tinker Auth strict mode requires --user when the command is non-interactive.';
    }

    protected function reportTinkerAuthError(string $message): void
    {
        if (method_exists($this, 'error')) {
            $this->error($message);
        }
    }

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
