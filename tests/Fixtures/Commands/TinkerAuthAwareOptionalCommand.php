<?php

declare(strict_types=1);

namespace Joke2k\TinkerAuth\Tests\Fixtures\Commands;

class TinkerAuthAwareOptionalCommand extends TinkerAuthAwareCommand
{
    protected $signature = 'test:tinker-auth-aware-optional';

    protected string $tinkerAuthMode = 'optional';
}
