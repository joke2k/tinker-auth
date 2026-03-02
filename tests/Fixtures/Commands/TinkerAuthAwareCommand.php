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

    public function handle(): int
    {
        $this->line((string) (auth()->user()?->email ?? 'guest'));

        return self::SUCCESS;
    }
}
