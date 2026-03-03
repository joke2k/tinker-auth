<?php

declare(strict_types=1);

namespace Joke2k\TinkerAuth\Commands;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Tinker\Console\TinkerCommand as BaseTinkerCommand;
use Joke2k\TinkerAuth\TinkerAuthManager;

class TinkerCommand extends BaseTinkerCommand
{
    public function __construct(private readonly TinkerAuthManager $authManager)
    {
        parent::__construct();
    }

    public function handle()
    {
        $authResult = $this->authenticateSession();

        if ($authResult === self::FAILURE) {
            return self::FAILURE;
        }

        return $this->runTinker();
    }

    protected function runTinker(): int
    {
        return (int) parent::handle();
    }

    protected function authenticateSession(): int
    {
        if ($this->authManager->guard()->user() !== null) {
            return self::SUCCESS;
        }

        $mode = $this->authManager->resolveMode();

        if ($mode === 'disabled') {
            return self::SUCCESS;
        }

        if (! $this->input->isInteractive()) {
            if ($mode === 'strict') {
                $this->error('Tinker Auth strict mode requires interactive authentication.');

                return self::FAILURE;
            }

            return self::SUCCESS;
        }

        if ($mode === 'strict') {
            $this->info((string) config('tinker-auth.prompt.strict_message'));
        }

        if ($mode === 'optional') {
            $this->info((string) config('tinker-auth.prompt.optional_message'));
        }

        $user = $this->promptForAuthenticatedUser($mode);

        if ($user instanceof Authenticatable) {
            $this->authManager->setActingUser($user);
            $this->info('Authenticated for this Tinker session.');

            return self::SUCCESS;
        }

        if ($mode === 'strict') {
            $this->error('Unable to authenticate this Tinker session.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function promptForAuthenticatedUser(string $mode): ?Authenticatable
    {
        $attempts = max(1, (int) config('tinker-auth.max_attempts', 3));
        $loginLabel = (string) config('tinker-auth.prompt.login_label', 'Login');
        $passwordLabel = (string) config('tinker-auth.prompt.password_label', 'Password');

        for ($i = 1; $i <= $attempts; $i++) {
            $identifier = trim((string) $this->ask($loginLabel));

            if ($identifier === '') {
                if ($mode === 'optional') {
                    return null;
                }

                $this->error('A login value is required in strict mode.');
                continue;
            }

            $password = (string) $this->secret($passwordLabel);
            $user = $this->authManager->attemptLogin($identifier, $password);

            if ($user instanceof Authenticatable) {
                return $user;
            }

            $this->error('Invalid credentials.');
        }

        return null;
    }
}
