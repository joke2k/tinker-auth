<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Joke2k\TinkerAuth\Commands\TinkerCommand;
use Joke2k\TinkerAuth\Tests\Fixtures\User;
use Symfony\Component\Console\Input\ArrayInput;

it('injects an include file that exposes the authenticated user as $_u', function (): void {
    $user = User::query()->create([
        'email' => 'scope-user@example.com',
        'password' => Hash::make('secret-pass'),
    ]);

    auth()->guard()->setUser($user);

    $command = new class extends TinkerCommand
    {
        public function exposedInjectInclude(ArrayInput $input): ?string
        {
            $this->input = $input;

            return $this->injectTinkerAuthUserInclude();
        }
    };
    $command->setLaravel(app());

    $input = new ArrayInput(['include' => []], $command->getDefinition());
    $file = $command->exposedInjectInclude($input);

    expect($file)->toBeString()
        ->and($input->getArgument('include'))->toBeArray()
        ->and($input->getArgument('include')[0])->toBe($file)
        ->and(is_file($file))->toBeTrue();

    /** @var mixed $_u */
    $_u = null;

    require $file;

    expect($_u)->toBe($user);

    @unlink($file);
});

it('injects an include file that sets $_u to null when no user is authenticated', function (): void {
    /** @var \Illuminate\Auth\AuthManager $authManager */
    $authManager = app('auth');
    $authManager->forgetGuards();

    $command = new class extends TinkerCommand
    {
        public function exposedInjectInclude(ArrayInput $input): ?string
        {
            $this->input = $input;

            return $this->injectTinkerAuthUserInclude();
        }
    };
    $command->setLaravel(app());

    $input = new ArrayInput(['include' => []], $command->getDefinition());
    $file = $command->exposedInjectInclude($input);

    expect($file)->toBeString()
        ->and(is_file($file))->toBeTrue();

    /** @var mixed $_u */
    $_u = 'sentinel';

    require $file;

    expect($_u)->toBeNull();

    @unlink($file);
});
