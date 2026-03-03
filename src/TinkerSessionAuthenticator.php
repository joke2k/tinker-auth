<?php

declare(strict_types=1);

namespace Joke2k\TinkerAuth;

use Illuminate\Contracts\Auth\Authenticatable;
use RuntimeException;
use Symfony\Component\Console\Exception\InvalidArgumentException as ConsoleInvalidArgumentException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class TinkerSessionAuthenticator
{
    public function __construct(private readonly TinkerAuthManager $authManager)
    {
    }

    public function authenticate(InputInterface $input, OutputInterface $output): void
    {
        if ($this->authManager->guard()->user() !== null) {
            return;
        }

        $mode = $this->authManager->resolveMode();
        $identifierFromOption = $this->resolveUserOption($input);

        if ($mode === 'disabled') {
            return;
        }

        if ($identifierFromOption !== null) {
            if (! $input->isInteractive()) {
                throw new RuntimeException('Tinker Auth --user requires interactive input to prompt for password.');
            }

            $user = $this->promptForAuthenticatedUser($input, $output, 'strict', $identifierFromOption);

            if ($user instanceof Authenticatable) {
                $this->authManager->setActingUser($user);
                $output->writeln('<info>Authenticated for this Tinker session.</info>');
                return;
            }

            throw new RuntimeException('Unable to authenticate this Tinker session with the provided --user value.');
        }

        if (! $input->isInteractive()) {
            if ($mode === 'strict') {
                throw new RuntimeException('Tinker Auth strict mode requires interactive authentication.');
            }

            return;
        }

        $output->writeln((string) config("tinker-auth.prompt.{$mode}_message"));

        $user = $this->promptForAuthenticatedUser($input, $output, $mode);

        if ($user instanceof Authenticatable) {
            $this->authManager->setActingUser($user);
            $output->writeln('<info>Authenticated for this Tinker session.</info>');
            return;
        }

        if ($mode === 'strict') {
            throw new RuntimeException('Unable to authenticate this Tinker session.');
        }
    }

    private function promptForAuthenticatedUser(
        InputInterface $input,
        OutputInterface $output,
        string $mode,
        ?string $fixedIdentifier = null
    ): ?Authenticatable
    {
        $attempts = max(1, (int) config('tinker-auth.max_attempts', 3));
        $loginLabel = (string) config('tinker-auth.prompt.login_label', 'Login');
        $passwordLabel = (string) config('tinker-auth.prompt.password_label', 'Password');

        $helper = new QuestionHelper();

        for ($i = 1; $i <= $attempts; $i++) {
            $identifier = $fixedIdentifier;

            if ($identifier === null) {
                $identifier = $this->promptLogin($input, $output, $helper, $loginLabel);
            }

            if ($identifier === '') {
                if ($mode === 'optional') {
                    return null;
                }

                $output->writeln('<error>A login value is required in strict mode.</error>');
                continue;
            }

            $password = $this->promptPassword($input, $output, $helper, $passwordLabel);
            $user = $this->authManager->attemptLogin($identifier, $password);

            if ($user instanceof Authenticatable) {
                return $user;
            }

            $output->writeln('<error>Invalid credentials.</error>');
        }

        return null;
    }

    private function promptLogin(
        InputInterface $input,
        OutputInterface $output,
        QuestionHelper $helper,
        string $label
    ): string {
        if (function_exists('Laravel\\Prompts\\text')) {
            return trim((string) \Laravel\Prompts\text(label: $label, required: false));
        }

        $loginQuestion = new Question($label.': ');

        return trim((string) $helper->ask($input, $output, $loginQuestion));
    }

    private function promptPassword(
        InputInterface $input,
        OutputInterface $output,
        QuestionHelper $helper,
        string $label
    ): string {
        if (function_exists('Laravel\\Prompts\\password')) {
            return (string) \Laravel\Prompts\password(label: $label, required: true);
        }

        $passwordQuestion = new Question($label.': ');
        $passwordQuestion->setHidden(true);
        $passwordQuestion->setHiddenFallback(false);

        return (string) $helper->ask($input, $output, $passwordQuestion);
    }

    private function resolveUserOption(InputInterface $input): ?string
    {
        try {
            $value = $input->getOption('user');
        } catch (ConsoleInvalidArgumentException) {
            return null;
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
