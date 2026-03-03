<?php

declare(strict_types=1);

namespace Joke2k\TinkerAuth\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class TinkerAuthMode
{
    public function __construct(public readonly string $mode)
    {
    }
}
