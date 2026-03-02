<?php

declare(strict_types=1);

it('loads the default auth mode', function (): void {
    expect(config('tinker-auth.mode'))->toBe('optional');
});
