<?php

declare(strict_types=1);

namespace Joke2k\TinkerAuth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use InvalidArgumentException;
use Joke2k\TinkerAuth\Support\CredentialValidator;
use Joke2k\TinkerAuth\Support\UserResolver;

class TinkerAuthManager
{
    public function __construct(
        private readonly AuthFactory $auth,
        private readonly UserResolver $userResolver,
        private readonly CredentialValidator $credentialValidator,
    ) {}

    public function resolveMode(?string $modeOverride = null, bool $allowDisabled = true): string
    {
        $mode = strtolower(trim($modeOverride ?? (string) config('tinker-auth.mode', 'optional')));

        $allowed = $allowDisabled
            ? ['strict', 'optional', 'disabled']
            : ['strict', 'optional'];

        if (! in_array($mode, $allowed, true)) {
            $expected = implode(', ', $allowed);

            throw new InvalidArgumentException("Invalid Tinker Auth mode [{$mode}]. Expected one of: {$expected}.");
        }

        return $mode;
    }

    public function resolveCommandMode(?string $modeOverride = null): string
    {
        $fallback = (string) config('tinker-auth.command_trait.default_mode', 'strict');

        return $this->resolveMode($modeOverride ?? $fallback, false);
    }

    public function guardName(): string
    {
        return (string) (config('tinker-auth.guard') ?: config('auth.defaults.guard', 'web'));
    }

    public function guard(): Guard
    {
        return $this->auth->guard($this->guardName());
    }

    public function findUser(string $identifier): ?Authenticatable
    {
        return $this->userResolver->findByIdentifier($identifier);
    }

    /**
     * @return array<int, string>
     */
    public function suggestUserIdentifiers(): array
    {
        $limit = (int) config('tinker-auth.prompt.autocomplete_limit', 20);

        return $this->userResolver->suggestIdentifiers($limit);
    }

    public function attemptLogin(string $identifier, string $password): ?Authenticatable
    {
        $user = $this->findUser($identifier);

        if (! $user instanceof Authenticatable) {
            return null;
        }

        return $this->credentialValidator->validate($user, $password) ? $user : null;
    }

    public function setActingUser(Authenticatable $user): void
    {
        $guardName = $this->guardName();

        $this->auth->shouldUse($guardName);
        $this->auth->guard($guardName)->setUser($user);
    }
}
