<?php

declare(strict_types=1);

namespace Joke2k\TinkerAuth;

use Illuminate\Contracts\Auth\Authenticatable;

class TinkerAuth
{
    public function __construct(private readonly TinkerAuthManager $manager) {}

    public function mode(?string $override = null, bool $allowDisabled = true): string
    {
        return $this->manager->resolveMode($override, $allowDisabled);
    }

    public function actingUser(): ?Authenticatable
    {
        return $this->manager->guard()->user();
    }
}
