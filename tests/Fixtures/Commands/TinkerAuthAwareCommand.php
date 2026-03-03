<?php

declare(strict_types=1);

namespace Joke2k\TinkerAuth\Tests\Fixtures\Commands;

use Illuminate\Console\Command;
use Joke2k\TinkerAuth\Concerns\InteractsWithTinkerAuth;

class TinkerAuthAwareCommand extends Command
{
    use InteractsWithTinkerAuth;

    protected $signature = 'test:tinker-auth-aware';

    protected $description = 'Test command for Tinker auth trait';

    protected string $tinkerAuthMode = 'strict';

    public function handle(): int
    {
        $this->line((string) ($this->tinkerAuthUser()?->email ?? 'guest'));

        return self::SUCCESS;
    }

    protected function promptTinkerAuthPassword(): string
    {
        return 'secret-pass';
    }

    public function currentMode(): string
    {
        return $this->tinkerAuthMode;
    }
}
