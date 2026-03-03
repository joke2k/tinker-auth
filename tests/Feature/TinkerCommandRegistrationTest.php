<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

it('registers tinker command with user option', function (): void {
    // Trigger command registration callbacks.
    Artisan::call('list');

    $commands = Artisan::all();

    expect(($commands['tinker'] ?? null)?->getDefinition()->hasOption('user'))->toBeTrue();
});
