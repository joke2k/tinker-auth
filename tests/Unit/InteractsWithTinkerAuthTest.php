<?php

declare(strict_types=1);

use Illuminate\Console\ManuallyFailedException;
use Illuminate\Support\Facades\Hash;
use Joke2k\TinkerAuth\Tests\Fixtures\Commands\TinkerAuthAwareCommand;
use Joke2k\TinkerAuth\Tests\Fixtures\Commands\TinkerAuthAwareInvalidModeCommand;
use Joke2k\TinkerAuth\Tests\Fixtures\Commands\TinkerAuthAwareOptionalCommand;
use Joke2k\TinkerAuth\Tests\Fixtures\User;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

it('adds only the user option to commands', function (): void {
    $command = new TinkerAuthAwareCommand();

    expect($command->getDefinition()->hasOption('user'))->toBeTrue()
        ->and($command->getDefinition()->hasOption('auth-mode'))->toBeFalse();
});

it('fails in strict mode when user is missing on non-interactive input', function (): void {
    $command = new TinkerAuthAwareCommand();
    $command->setLaravel(app());

    $input = new ArrayInput([]);
    $input->setInteractive(false);

    expect(fn () => $command->run($input, new BufferedOutput()))
        ->toThrow(ManuallyFailedException::class, 'requires --user when the command is non-interactive');
});

it('runs as guest in optional mode when user is missing', function (): void {
    $command = new TinkerAuthAwareOptionalCommand();
    $command->setLaravel(app());

    $input = new ArrayInput([]);
    $input->setInteractive(false);

    $output = new BufferedOutput();
    $exitCode = $command->run($input, $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toContain('guest')
        ->and($command->currentMode())->toBe('optional');
});

it('sets the acting user from --user option', function (): void {
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

it('fails authentication when --user is provided with wrong password', function (): void {
    User::query()->create([
        'email' => 'runner@example.com',
        'password' => Hash::make('secret-pass'),
    ]);

    $command = new class extends TinkerAuthAwareCommand {
        protected function promptTinkerAuthPassword(): string
        {
            return 'wrong-pass';
        }
    };
    $command->setLaravel(app());

    $input = new ArrayInput(['--user' => 'runner@example.com']);
    $input->setInteractive(false);

    expect(fn () => $command->run($input, new BufferedOutput()))
        ->toThrow(ManuallyFailedException::class, 'Invalid credentials for the provided user.');
});

it('fails when both strict and optional attributes are present', function (): void {
    $command = new TinkerAuthAwareInvalidModeCommand();
    $command->setLaravel(app());

    $input = new ArrayInput([]);
    $input->setInteractive(false);

    expect(fn () => $command->run($input, new BufferedOutput()))
        ->toThrow(ManuallyFailedException::class, 'cannot declare both TinkerAuthStrict and TinkerAuthOptional');
});
