<?php

declare(strict_types=1);

namespace Joke2k\TinkerAuth;

use Illuminate\Support\ServiceProvider;
use Joke2k\TinkerAuth\Commands\InstallCommand;

class TinkerAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tinker-auth.php', 'tinker-auth');

        $this->app->singleton(TinkerAuth::class, static fn (): TinkerAuth => new TinkerAuth());
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

            $this->publishes([
                __DIR__.'/../database/migrations/create_tinker_auth_tables.php.stub' => database_path('migrations/'.date('Y_m_d_His').'_create_tinker_auth_tables.php'),
            ], 'tinker-auth-migrations');
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
