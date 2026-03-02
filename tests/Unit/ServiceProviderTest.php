<?php

declare(strict_types=1);

use Joke2k\TinkerAuth\TinkerAuth;
use Joke2k\TinkerAuth\TinkerAuthManager;

it('registers package config', function (): void {
    expect(config('tinker-auth.mode'))->toBe('optional');
});

it('binds package services', function (): void {
    expect(app(TinkerAuth::class))->toBeInstanceOf(TinkerAuth::class)
        ->and(app(TinkerAuthManager::class))->toBeInstanceOf(TinkerAuthManager::class);
});
