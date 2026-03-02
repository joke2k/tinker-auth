<?php

declare(strict_types=1);

namespace Joke2k\TinkerAuth\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'tinker-auth:install {--force : Overwrite files when publishing}';

    protected $description = 'Install the Tinker Auth package resources';

    public function handle(): int
    {
        $params = ['--provider' => 'Joke2k\\TinkerAuth\\TinkerAuthServiceProvider'];

        if ((bool) $this->option('force')) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', array_merge($params, ['--tag' => 'tinker-auth-config']));
        $this->call('vendor:publish', array_merge($params, ['--tag' => 'tinker-auth-migrations']));

        $this->info('Tinker Auth scaffolding published successfully.');

        return self::SUCCESS;
    }
}
