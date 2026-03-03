<?php

declare(strict_types=1);

use Joke2k\TinkerAuth\Commands\TinkerCommand;

it('registers the package tinker command implementation', function (): void {
    // Trigger command registration callbacks.
    Artisan::call('list');

    $commands = Artisan::all();

    expect($commands['tinker'] ?? null)->toBeInstanceOf(TinkerCommand::class)
        ->and(($commands['tinker'] ?? null)?->getDefinition()->hasOption('user'))->toBeTrue();
});
