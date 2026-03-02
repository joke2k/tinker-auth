<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(config('tinker-auth.middleware', ['web']))
    ->prefix(config('tinker-auth.route.prefix', 'tinker-auth'))
    ->name(config('tinker-auth.route.name', 'tinker-auth.'))
    ->group(function (): void {
        Route::get('/health', function () {
            return response()->json([
                'package' => 'tinker-auth',
                'status' => 'ok',
            ]);
        })->name('health');
    });
