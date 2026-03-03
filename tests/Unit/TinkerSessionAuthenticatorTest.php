<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Joke2k\TinkerAuth\TinkerSessionAuthenticator;
use Joke2k\TinkerAuth\Tests\Fixtures\User;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

it('skips re-authentication when a user is already set on the guard', function (): void {
    config()->set('tinker-auth.mode', 'strict');

    $user = User::query()->create([
        'email' => 'already-authenticated@example.com',
        'password' => Hash::make('secret-pass'),
    ]);

    auth()->setUser($user);

    $input = new ArrayInput(['--execute' => '1 + 1']);
    $input->setInteractive(false);

    $authenticator = app(TinkerSessionAuthenticator::class);

    expect(fn () => $authenticator->authenticate($input, new BufferedOutput()))->not->toThrow(\Throwable::class)
        ->and(auth()->user()?->getAuthIdentifier())->toBe($user->getAuthIdentifier());
});
