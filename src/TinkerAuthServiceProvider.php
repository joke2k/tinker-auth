<?php

declare(strict_types=1);

namespace Joke2k\TinkerAuth;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Application as ArtisanApplication;
use Illuminate\Support\ServiceProvider;
use Joke2k\TinkerAuth\Commands\InstallCommand;
use Joke2k\TinkerAuth\Commands\TinkerCommand;
use Joke2k\TinkerAuth\Support\CredentialValidator;
use Joke2k\TinkerAuth\Support\UserResolver;
use Laravel\Tinker\Console\TinkerCommand as BaseTinkerCommand;
use Symfony\Component\Console\Input\InputOption;

class TinkerAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tinker-auth.php', 'tinker-auth');

        $this->app->singleton(UserResolver::class);
        $this->app->singleton(CredentialValidator::class);
        $this->app->singleton(TinkerAuthManager::class);
        $this->app->singleton(TinkerSessionAuthenticator::class);
        $this->app->singleton(TinkerAuth::class);

        $this->overrideTinkerCommandBinding();
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
                $this->app->booted(function (): void {
                    // Register this callback after all providers have registered their console callbacks.
                    ArtisanApplication::starting(function ($artisan): void {
                        $this->overrideTinkerCommandBinding();

                        $tinker = $this->app->make('command.tinker');

                        if (! $tinker->getDefinition()->hasOption('user')) {
                            $tinker->getDefinition()->addOption(new InputOption(
                                'user',
                                'u',
                                InputOption::VALUE_OPTIONAL,
                                'Use this user identifier as login and prompt for password'
                            ));
                        }

                        $artisan->add($tinker);
                    });
                });

                $this->commands([
                    'command.tinker',
                ]);

                $this->app['events']->listen(CommandStarting::class, function (CommandStarting $event): void {
                    if ($event->command !== 'tinker') {
                        return;
                    }

                    $this->app->make(TinkerSessionAuthenticator::class)->authenticate($event->input, $event->output);
                });
            }
        }
    }

    private function overrideTinkerCommandBinding(): void
    {
        if (! class_exists(BaseTinkerCommand::class)) {
            return;
        }

        // Force the canonical tinker binding to resolve to our command.
        $this->app->singleton('command.tinker', fn ($app): TinkerCommand => $app->make(TinkerCommand::class));
    }
}
