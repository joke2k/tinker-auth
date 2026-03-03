<?php

declare(strict_types=1);

it('registers tinker command with user option', function (): void {
    // Trigger command registration callbacks.
    Artisan::call('list');

    $commands = Artisan::all();

    expect(($commands['tinker'] ?? null)?->getDefinition()->hasOption('user'))->toBeTrue();
});
