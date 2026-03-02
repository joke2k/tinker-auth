<?php

declare(strict_types=1);

namespace Joke2k\TinkerAuth;

use Illuminate\Console\Events\ArtisanStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Joke2k\TinkerAuth\Commands\InstallCommand;
use Joke2k\TinkerAuth\Commands\TinkerCommand;
use Joke2k\TinkerAuth\Support\CredentialValidator;
use Joke2k\TinkerAuth\Support\UserResolver;
use Laravel\Tinker\Console\TinkerCommand as BaseTinkerCommand;

class TinkerAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tinker-auth.php', 'tinker-auth');

        $this->app->singleton(UserResolver::class);
        $this->app->singleton(CredentialValidator::class);
        $this->app->singleton(TinkerAuthManager::class);
        $this->app->singleton(TinkerAuth::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/tinker-auth.php' => config_path('tinker-auth.php'),
        ], 'tinker-auth-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);

            if (class_exists(BaseTinkerCommand::class)) {
                Event::listen(ArtisanStarting::class, function ($event): void {
                    $event->artisan->resolveCommands([
                        TinkerCommand::class,
                    ]);
                });
            }
        }
    }
}
