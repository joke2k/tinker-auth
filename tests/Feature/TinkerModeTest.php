<?php

declare(strict_types=1);

use Joke2k\TinkerAuth\Commands\TinkerCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

it('fails in strict mode when tinker input is non-interactive', function (): void {
    config()->set('tinker-auth.mode', 'strict');

    $command = new class extends TinkerCommand {
        protected function runTinker(): int
        {
            return self::SUCCESS;
        }
    };

    $command->setLaravel(app());

    $input = new ArrayInput(['--execute' => '1 + 1']);
    $input->setInteractive(false);

    $output = new BufferedOutput();

    $exitCode = $command->run($input, $output);

    expect($exitCode)->toBe(1)
        ->and($output->fetch())->toContain('strict mode requires interactive authentication');
});

it('continues in optional mode when tinker input is non-interactive', function (): void {
    config()->set('tinker-auth.mode', 'optional');

    $command = new class extends TinkerCommand {
        protected function runTinker(): int
        {
            return self::SUCCESS;
        }
    };

    $command->setLaravel(app());

    $input = new ArrayInput(['--execute' => '1 + 1']);
    $input->setInteractive(false);

    $exitCode = $command->run($input, new BufferedOutput());

    expect($exitCode)->toBe(0);
});

it('continues in disabled mode without authentication', function (): void {
    config()->set('tinker-auth.mode', 'disabled');

    $command = new class extends TinkerCommand {
        protected function runTinker(): int
        {
            return self::SUCCESS;
        }
    };

    $command->setLaravel(app());

    $input = new ArrayInput(['--execute' => '1 + 2']);
    $input->setInteractive(false);

    $exitCode = $command->run($input, new BufferedOutput());

    expect($exitCode)->toBe(0);
});
