<?php

declare(strict_types=1);

namespace Joke2k\TinkerAuth\Commands;

use Illuminate\Console\ManuallyFailedException;
use InvalidArgumentException;
use Joke2k\TinkerAuth\Concerns\InteractsWithTinkerAuth;
use Joke2k\TinkerAuth\TinkerAuthManager;
use Laravel\Tinker\Console\TinkerCommand as BaseTinkerCommand;
use Symfony\Component\Console\Input\Input;
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
        $includeFile = $this->injectTinkerAuthUserInclude();

        try {
            return (int) parent::handle();
        } finally {
            if (is_string($includeFile) && is_file($includeFile)) {
                @unlink($includeFile);
            }
        }
    }

    protected function injectTinkerAuthUserInclude(): ?string
    {
        if (! $this->input instanceof Input) {
            return null;
        }

        $includes = $this->argument('include');

        if (! is_array($includes)) {
            return null;
        }

        $file = tempnam(sys_get_temp_dir(), 'tinker-auth-u-');

        if (! is_string($file)) {
            return null;
        }

        $code = <<<'PHP'
<?php
$_u = app(\Joke2k\TinkerAuth\TinkerAuth::class)->actingUser();
PHP;

        if (@file_put_contents($file, $code) === false) {
            @unlink($file);

            return null;
        }

        $this->input->setArgument(
            'include',
            array_values(array_merge([$file], $includes)),
        );

        return $file;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->authInitializationException = null;

        try {
            $this->initializeInteractsWithTinkerAuth($input, $output);
        } catch (ManuallyFailedException $manuallyFailedException) {
            $this->authInitializationException = $manuallyFailedException;
        } catch (InvalidArgumentException $invalidArgumentException) {
            $this->authInitializationException = new ManuallyFailedException($invalidArgumentException->getMessage());
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
