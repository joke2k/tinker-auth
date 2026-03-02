<?php

declare(strict_types=1);

it('returns package health information', function (): void {
    $response = $this->get('/tinker-auth/health');

    $response
        ->assertOk()
        ->assertJson([
            'package' => 'tinker-auth',
            'status' => 'ok',
        ]);
});
