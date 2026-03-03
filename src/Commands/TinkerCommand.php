<?php

declare(strict_types=1);

namespace Joke2k\TinkerAuth\Commands;

use Joke2k\TinkerAuth\Concerns\InteractsWithTinkerAuth;
use Joke2k\TinkerAuth\TinkerAuthManager;
use Illuminate\Console\ManuallyFailedException;
use Laravel\Tinker\Console\TinkerCommand as BaseTinkerCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TinkerCommand extends BaseTinkerCommand
{
    use InteractsWithTinkerAuth {
        initialize as private initializeInteractsWithTinkerAuth;
    }

    private ?ManuallyFailedException $authInitializationException = null;

    public function handle()
    {
        if ($this->authInitializationException instanceof \Illuminate\Console\ManuallyFailedException) {
            $this->error($this->authInitializationException->getMessage());

            return self::FAILURE;
        }

        return $this->runTinker();
    }

    protected function runTinker(): int
    {
        return (int) parent::handle();
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        try {
            $this->initializeInteractsWithTinkerAuth($input, $output);
        } catch (ManuallyFailedException $manuallyFailedException) {
            $this->authInitializationException = $manuallyFailedException;
        }
    }

    protected function resolveEffectiveTinkerAuthMode(TinkerAuthManager $manager): string
    {
        return $manager->resolveMode($this->resolveTinkerAuthMode(), true);
    }

    protected function resolveTinkerAuthMode(): ?string
    {
        return (string) config('tinker-auth.mode', 'optional');
    }

    protected function onTinkerAuthPromptStart(string $mode, OutputInterface $output): void
    {
        if ($mode === 'strict') {
            $this->info((string) config('tinker-auth.prompt.strict_message'));
            return;
        }

        if ($mode === 'optional') {
            $this->info((string) config('tinker-auth.prompt.optional_message'));
        }
    }

    protected function onTinkerAuthSuccess(OutputInterface $output): void
    {
        $this->info('Authenticated for this Tinker session.');
    }

    protected function strictNonInteractiveAuthMessage(): string
    {
        return 'Tinker Auth strict mode requires interactive authentication.';
    }
}
