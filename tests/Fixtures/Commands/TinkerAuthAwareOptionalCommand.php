<?php

declare(strict_types=1);

namespace Joke2k\TinkerAuth\Tests\Fixtures\Commands;

use Joke2k\TinkerAuth\Attributes\TinkerAuthOptional;

#[TinkerAuthOptional]
class TinkerAuthAwareOptionalCommand extends TinkerAuthAwareCommand
{
    protected $signature = 'test:tinker-auth-aware-optional';
}
