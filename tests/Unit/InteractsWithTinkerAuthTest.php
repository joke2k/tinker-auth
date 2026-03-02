<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Joke2k\TinkerAuth\Tests\Fixtures\Commands\TinkerAuthAwareCommand;
use Joke2k\TinkerAuth\Tests\Fixtures\User;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

it('adds the user and auth-mode options to commands', function (): void {
    $command = new TinkerAuthAwareCommand();

    expect($command->getDefinition()->hasOption('user'))->toBeTrue()
        ->and($command->getDefinition()->hasOption('auth-mode'))->toBeTrue();
});

it('fails in strict mode when user is missing on non-interactive input', function (): void {
    config()->set('tinker-auth.command_trait.default_mode', 'strict');

    $command = new TinkerAuthAwareCommand();
    $command->setLaravel(app());

    $input = new ArrayInput([]);
    $input->setInteractive(false);

    expect(fn () => $command->run($input, new BufferedOutput()))
        ->toThrow(\RuntimeException::class, 'requires --user when the command is non-interactive');
});

it('runs as guest in optional mode when user is missing', function (): void {
    config()->set('tinker-auth.command_trait.default_mode', 'optional');

    $command = new TinkerAuthAwareCommand();
    $command->setLaravel(app());

    $input = new ArrayInput([]);
    $input->setInteractive(false);

    $output = new BufferedOutput();
    $exitCode = $command->run($input, $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toContain('guest');
});

it('sets the acting user from --user option', function (): void {
    config()->set('tinker-auth.command_trait.default_mode', 'strict');

    User::query()->create([
        'email' => 'runner@example.com',
        'password' => Hash::make('secret-pass'),
    ]);

    $command = new TinkerAuthAwareCommand();
    $command->setLaravel(app());

    $input = new ArrayInput(['--user' => 'runner@example.com']);
    $input->setInteractive(false);

    $output = new BufferedOutput();
    $exitCode = $command->run($input, $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toContain('runner@example.com');
});
