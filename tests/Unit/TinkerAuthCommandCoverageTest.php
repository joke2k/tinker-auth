<?php

declare(strict_types=1);

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Joke2k\TinkerAuth\Commands\TinkerCommand;
use Joke2k\TinkerAuth\Concerns\InteractsWithTinkerAuth;
use Joke2k\TinkerAuth\Tests\Fixtures\User;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

it('prints strict and optional prompt messages and success message', function (): void {
    $command = new class extends TinkerCommand
    {
        /** @var array<int, string> */
        public array $messages = [];

        protected function runTinker(): int
        {
            return self::SUCCESS;
        }

        public function info($string, $verbosity = null): void
        {
            $this->messages[] = (string) $string;
        }

        public function exposePromptStart(string $mode): void
        {
            $this->onTinkerAuthPromptStart($mode, new BufferedOutput);
        }

        public function exposeSuccess(): void
        {
            $this->onTinkerAuthSuccess(new BufferedOutput);
        }
    };

    config()->set('tinker-auth.prompt.strict_message', 'STRICT MESSAGE');
    config()->set('tinker-auth.prompt.optional_message', 'OPTIONAL MESSAGE');

    $command->exposePromptStart('strict');
    $command->exposePromptStart('optional');
    $command->exposeSuccess();

    expect($command->messages)->toBe([
        'STRICT MESSAGE',
        'OPTIONAL MESSAGE',
        'Authenticated for this Tinker session.',
    ]);
});

it('supports command auth mode from a tinkerAuthMode method', function (): void {
    $command = new class extends Command
    {
        use InteractsWithTinkerAuth;

        protected $signature = 'test:tinker-auth-mode-method';

        public function handle(): int
        {
            $this->line((string) (auth()->guard()->user()?->email ?? 'guest'));

            return self::SUCCESS;
        }

        protected function tinkerAuthMode(): string
        {
            return 'optional';
        }
    };
    $command->setLaravel(app());

    $input = new ArrayInput([]);
    $input->setInteractive(false);
    $output = new BufferedOutput;
    $exitCode = $command->run($input, $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toContain('guest');
});

it('handles interactive strict mode attempts with fallback ask/secret methods', function (): void {
    config()->set('tinker-auth.max_attempts', 3);

    User::query()->create([
        'email' => 'valid@example.com',
        'password' => Hash::make('secret-pass'),
    ]);

    $command = new class extends Command
    {
        use InteractsWithTinkerAuth;

        protected $signature = 'test:tinker-auth-interactive-coverage';

        /** @var array<int, string> */
        private array $logins = ['', 'missing@example.com', 'valid@example.com'];

        /** @var array<int, string> */
        private array $passwords = ['wrong-pass', 'secret-pass'];

        public function handle(): int
        {
            $this->line((string) (auth()->guard()->user()?->email ?? 'guest'));

            return self::SUCCESS;
        }

        public function ask($question, $default = null): string
        {
            return array_shift($this->logins) ?? '';
        }

        public function secret($question, $fallback = true): string
        {
            return array_shift($this->passwords) ?? '';
        }

        protected function promptTinkerAuthLogin(string $mode): string
        {
            return trim($this->ask('Login'));
        }

        protected function promptTinkerAuthPassword(): string
        {
            return $this->secret('Password');
        }
    };
    $command->setLaravel(app());

    $input = new ArrayInput([]);
    $input->setInteractive(true);
    $output = new BufferedOutput;
    $exitCode = $command->run($input, $output);
    $text = $output->fetch();

    expect($exitCode)->toBe(0)
        ->and($text)->toContain('A login value is required in strict mode.')
        ->and($text)->toContain('Invalid credentials.')
        ->and($text)->toContain('valid@example.com');
});

it('captures invalid argument from initialize as command failure', function (): void {
    $command = new class extends TinkerCommand
    {
        protected function runTinker(): int
        {
            return self::SUCCESS;
        }

        protected function resolveTinkerAuthMode(): ?string
        {
            return 'not-a-valid-mode';
        }
    };
    $command->setLaravel(app());

    $input = new ArrayInput(['--execute' => '1 + 1']);
    $input->setInteractive(false);
    $output = new BufferedOutput;

    $exitCode = $command->run($input, $output);

    expect($exitCode)->toBe(1)
        ->and($output->fetch())->toContain('Invalid Tinker Auth mode [not-a-valid-mode].');
});
