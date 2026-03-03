<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Joke2k\TinkerAuth\Tests\Fixtures\User;
use Joke2k\TinkerAuth\TinkerAuth;
use Joke2k\TinkerAuth\TinkerAuthManager;

it('exposes mode and current acting user', function (): void {
    $user = User::query()->create([
        'email' => 'active@example.com',
        'password' => Hash::make('secret-pass'),
    ]);

    app(TinkerAuthManager::class)->setActingUser($user);

    $tinkerAuth = app(TinkerAuth::class);

    expect($tinkerAuth->mode('strict'))->toBe('strict')
        ->and($tinkerAuth->actingUser()?->getAuthIdentifier())->toBe($user->getAuthIdentifier());
});
