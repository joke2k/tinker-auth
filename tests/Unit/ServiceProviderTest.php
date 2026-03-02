<?php

declare(strict_types=1);

use Joke2k\TinkerAuth\TinkerAuth;

it('registers package config', function (): void {
    expect(config('tinker-auth.enabled'))->toBeTrue();
});

it('binds package service', function (): void {
    expect(app(TinkerAuth::class))->toBeInstanceOf(TinkerAuth::class);
});
