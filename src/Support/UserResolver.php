<?php

declare(strict_types=1);

namespace Joke2k\TinkerAuth\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

class UserResolver
{
    public function __construct(private readonly AuthFactory $auth)
    {
    }

    public function findByIdentifier(string $identifier): ?Authenticatable
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        $guardName = (string) (config('tinker-auth.guard') ?: config('auth.defaults.guard', 'web'));
        $column = (string) config('tinker-auth.username_column', 'email');
        $guard = $this->auth->guard($guardName);

        if (! method_exists($guard, 'getProvider')) {
            return null;
        }

        $provider = $guard->getProvider();

        return $provider->retrieveByCredentials([$column => $identifier]);
    }
}
