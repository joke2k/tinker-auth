<?php

declare(strict_types=1);

namespace Joke2k\TinkerAuth\Tests\Fixtures\Commands;

use Joke2k\TinkerAuth\Attributes\TinkerAuthOptional;
use Joke2k\TinkerAuth\Attributes\TinkerAuthStrict;

#[TinkerAuthStrict]
#[TinkerAuthOptional]
class TinkerAuthAwareInvalidModeCommand extends TinkerAuthAwareCommand
{
    protected $signature = 'test:tinker-auth-aware-invalid-mode';
}
