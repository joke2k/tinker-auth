<?php

declare(strict_types=1);

use Joke2k\TinkerAuth\Commands\InstallCommand;
use Joke2k\TinkerAuth\TinkerAuthServiceProvider;

it('publishes config without force by default', function (): void {
    $command = new class extends InstallCommand
    {
        /** @var array<int, array{0: string, 1: array<string, mixed>}> */
        public array $calls = [];

        public function option($key = null): string|bool|null
        {
            return $key === 'force' ? false : null;
        }

        /**
         * @param array<string, mixed> $arguments
         */
        public function call($command, array $arguments = []): int
        {
            $this->calls[] = [(string) $command, $arguments];

            return self::SUCCESS;
        }

        public function info($string, $verbosity = null): void {}
    };

    $exitCode = $command->handle();

    expect($exitCode)->toBe(0)
        ->and($command->calls)->toHaveCount(1)
        ->and($command->calls[0][0])->toBe('vendor:publish')
        ->and($command->calls[0][1])->toMatchArray([
            '--provider' => TinkerAuthServiceProvider::class,
            '--tag' => 'tinker-auth-config',
        ])
        ->and($command->calls[0][1])->not->toHaveKey('--force');
});

it('publishes config with force when requested', function (): void {
    $command = new class extends InstallCommand
    {
        /** @var array<int, array{0: string, 1: array<string, mixed>}> */
        public array $calls = [];

        public function option($key = null): string|bool|null
        {
            return $key === 'force' ? true : null;
        }

        /**
         * @param array<string, mixed> $arguments
         */
        public function call($command, array $arguments = []): int
        {
            $this->calls[] = [(string) $command, $arguments];

            return self::SUCCESS;
        }

        public function info($string, $verbosity = null): void {}
    };

    $exitCode = $command->handle();

    expect($exitCode)->toBe(0)
        ->and($command->calls)->toHaveCount(1)
        ->and($command->calls[0][1])->toMatchArray([
            '--provider' => TinkerAuthServiceProvider::class,
            '--tag' => 'tinker-auth-config',
            '--force' => true,
        ]);
});
