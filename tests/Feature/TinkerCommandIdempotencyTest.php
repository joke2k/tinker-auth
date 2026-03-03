<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Joke2k\TinkerAuth\Commands\TinkerCommand;
use Joke2k\TinkerAuth\TinkerAuthManager;
use Joke2k\TinkerAuth\Tests\Fixtures\User;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

it('does not prompt again when user is already authenticated', function (): void {
    config()->set('tinker-auth.mode', 'strict');

    $user = User::query()->create([
        'email' => 'already-auth@example.com',
        'password' => Hash::make('secret-pass'),
    ]);

    app(TinkerAuthManager::class)->setActingUser($user);

    $command = new class(app(TinkerAuthManager::class)) extends TinkerCommand {
        protected function runTinker(): int
        {
            return self::SUCCESS;
        }
    };

    $command->setLaravel(app());

    $input = new ArrayInput(['--execute' => '1 + 1']);
    $input->setInteractive(true);

    $output = new BufferedOutput();
    $exitCode = $command->run($input, $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->not->toContain('Login');
});
