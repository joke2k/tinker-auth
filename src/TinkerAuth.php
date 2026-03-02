<?php

declare(strict_types=1);

namespace Joke2k\TinkerAuth;

class TinkerAuth
{
    public function enabled(): bool
    {
        return (bool) config('tinker-auth.enabled', true);
    }
}
