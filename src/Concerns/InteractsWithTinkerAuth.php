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
                'Run the command as the given user identifier'
            ));
        }

        if ((bool) config('tinker-auth.command_trait.allow_mode_override', true) && ! $definition->hasOption('auth-mode')) {
            $definition->addOption(new InputOption(
                'auth-mode',
                null,
                InputOption::VALUE_OPTIONAL,
                'Override auth mode for this command (strict|optional)'
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

        $modeOverride = $input->hasOption('auth-mode')
            ? $this->normalizeInputOption($input->getOption('auth-mode'))
            : null;

        $mode = $manager->resolveCommandMode($modeOverride);
        $identifier = $this->normalizeInputOption($input->getOption('user'));

        if ($identifier === null) {
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
        }

        $user = $manager->findUser($identifier);

        if (! $user instanceof Authenticatable) {
            throw new RuntimeException("Unable to resolve user [{$identifier}] for this command.");
        }

        $manager->setActingUser($user);

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
