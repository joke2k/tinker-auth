<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Joke2k\TinkerAuth\TinkerAuthManager;
use Joke2k\TinkerAuth\Tests\Fixtures\User;

it('resolves configured mode', function (): void {
    $manager = app(TinkerAuthManager::class);

    expect($manager->resolveMode())->toBe('optional')
        ->and($manager->resolveMode('strict'))->toBe('strict');
});

it('authenticates credentials with the default provider', function (): void {
    $user = User::query()->create([
        'email' => 'jane@example.com',
        'password' => Hash::make('secret-pass'),
    ]);

    $manager = app(TinkerAuthManager::class);
    $resolved = $manager->attemptLogin('jane@example.com', 'secret-pass');

    expect($resolved?->getAuthIdentifier())->toBe($user->getAuthIdentifier());
});

it('sets the acting user on the configured guard', function (): void {
    $user = User::query()->create([
        'email' => 'john@example.com',
        'password' => Hash::make('password-123'),
    ]);

    $manager = app(TinkerAuthManager::class);
    $manager->setActingUser($user);

    expect(auth()->user()?->getAuthIdentifier())->toBe($user->getAuthIdentifier());
});

it('returns suggested user identifiers for autocomplete', function (): void {
    User::query()->create([
        'email' => 'alpha@example.com',
        'password' => Hash::make('password-123'),
    ]);
    User::query()->create([
        'email' => 'beta@example.com',
        'password' => Hash::make('password-456'),
    ]);

    $manager = app(TinkerAuthManager::class);
    $suggestions = $manager->suggestUserIdentifiers();

    expect($suggestions)->toContain('alpha@example.com')
        ->and($suggestions)->toContain('beta@example.com');
});
