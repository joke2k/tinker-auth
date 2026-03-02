<?php

declare(strict_types=1);

namespace Joke2k\TinkerAuth\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

class CredentialValidator
{
    public function __construct(private readonly AuthFactory $auth)
    {
    }

    public function validate(Authenticatable $user, string $password): bool
    {
        if ($password === '') {
            return false;
        }

        $guardName = (string) (config('tinker-auth.guard') ?: config('auth.defaults.guard', 'web'));
        $guard = $this->auth->guard($guardName);

        if (! method_exists($guard, 'getProvider')) {
            return false;
        }

        return $guard->getProvider()->validateCredentials($user, ['password' => $password]);
    }
}
