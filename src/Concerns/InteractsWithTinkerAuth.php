<?php

declare(strict_types=1);

namespace Joke2k\TinkerAuth\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Joke2k\TinkerAuth\TinkerAuthManager;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

trait InteractsWithTinkerAuth
{
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

        $this->initializeTinkerAuth($input);
    }

    protected function initializeTinkerAuth(InputInterface $input): ?Authenticatable
    {
        $manager = app(TinkerAuthManager::class);
        $identifier = $this->normalizeInputOption($input->getOption('user'));
        $mode = $manager->resolveCommandMode($this->resolveTinkerAuthMode());

        if ($identifier !== null) {
            $user = $this->authenticateByCredentials($manager, $identifier);

            $manager->setActingUser($user);

            return $user;
        }

        if ($mode === 'optional') {
            return null;
        }

        if (! $input->isInteractive()) {
            throw new RuntimeException('Tinker Auth strict mode requires --user when the command is non-interactive.');
        }

        $identifier = trim((string) $this->ask((string) config('tinker-auth.prompt.login_label', 'Login')));

        if ($identifier === '') {
            throw new RuntimeException('Tinker Auth strict mode requires a non-empty user identifier.');
        }

        $user = $this->authenticateByCredentials($manager, $identifier);

        $manager->setActingUser($user);

        return $user;
    }

    protected function resolveTinkerAuthMode(): ?string
    {
        if (method_exists($this, 'tinkerAuthMode')) {
            /** @var mixed $mode */
            $mode = $this->tinkerAuthMode();

            return is_string($mode) ? $mode : null;
        }

        if (property_exists($this, 'tinkerAuthMode') && is_string($this->tinkerAuthMode)) {
            return $this->tinkerAuthMode;
        }

        return null;
    }

    protected function promptTinkerAuthPassword(): string
    {
        return (string) $this->secret((string) config('tinker-auth.prompt.password_label', 'Password'));
    }

    private function authenticateByCredentials(TinkerAuthManager $manager, string $identifier): Authenticatable
    {
        $password = $this->promptTinkerAuthPassword();
        $user = $manager->attemptLogin($identifier, $password);

        if (! $user instanceof Authenticatable) {
            throw new RuntimeException('Invalid credentials for the provided user.');
        }

        return $user;
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
