<?php

declare(strict_types=1);

namespace Joke2k\TinkerAuth\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Database\Eloquent\Model;

class UserResolver
{
    public function __construct(private readonly AuthFactory $auth) {}

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

    /**
     * @return array<int, string>
     */
    public function suggestIdentifiers(int $limit = 20): array
    {
        $guardName = (string) (config('tinker-auth.guard') ?: config('auth.defaults.guard', 'web'));
        $column = (string) config('tinker-auth.username_column', 'email');
        $providerName = (string) config("auth.guards.{$guardName}.provider", '');
        $modelClass = config("auth.providers.{$providerName}.model");

        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            return [];
        }

        $model = new $modelClass;

        if (! $model instanceof Model) {
            return [];
        }

        return $modelClass::query()
            ->whereNotNull($column)
            ->orderBy($column)
            ->limit(max(1, $limit))
            ->pluck($column)
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => trim($value))
            ->unique()
            ->values()
            ->all();
    }
}
