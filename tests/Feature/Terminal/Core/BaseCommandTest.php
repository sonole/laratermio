<?php

use App\Models\TerminalCommand;
use App\Terminal\Commands\BaseCommand;
use App\Terminal\Contracts\TerminalCommandContract;
use App\Terminal\TerminalResponse;

require_once __DIR__.'/Support.php';

beforeEach(function () {
    TerminalCoreProbeCommand::$received = [];
    TerminalCoreProbeCommand::$options = [];
});

describe('BaseCommand contract', function () {
    it('is the base of every terminal command', function () {
        $command = new TerminalCoreProbeCommand;

        expect($command)->toBeInstanceOf(BaseCommand::class)
            ->and($command)->toBeInstanceOf(TerminalCommandContract::class);
    });

    it('lists commands in the explore help group unless told otherwise', function () {
        expect((new TerminalCoreProbeCommand)->helpGroup())->toBe('explore');
    });

    it('has no help options by default', function () {
        expect((new TerminalCoreProbeCommand)->helpOptions())->toBe([]);
    });

    it('cannot have its handle() overridden', function () {
        expect((new ReflectionMethod(BaseCommand::class, 'handle'))->isFinal())->toBeTrue();
    });
});

describe('BaseCommand::handle', function () {
    it('hands the argument to execute()', function () {
        $response = (new TerminalCoreProbeCommand)->handle('value');

        expect(TerminalCoreProbeCommand::$received)->toBe(['value'])
            ->and($response)->toBeInstanceOf(TerminalResponse::class)
            ->and($response->html)->toBe("probe:'value'");
    });

    it('hands null to execute() when there is no argument', function () {
        (new TerminalCoreProbeCommand)->handle(null);

        expect(TerminalCoreProbeCommand::$received)->toBe([null]);
    });

    it('does not treat an empty string as a help request', function () {
        (new TerminalCoreProbeCommand)->handle('');

        expect(TerminalCoreProbeCommand::$received)->toBe(['']);
    });

    it('does not treat other flags as a help request', function (string $arg) {
        (new TerminalCoreProbeCommand)->handle($arg);

        expect(TerminalCoreProbeCommand::$received)->toBe([$arg]);
    })->with(['-H', '--HELP', '-help', 'help', '-h extra']);

    it('answers -h and --help itself without running execute()', function (string $flag) {
        $response = (new TerminalCoreProbeCommand)->handle($flag);

        expect($response->type)->toBe('echo')
            ->and($response->html)->toContain('<p class="t-header">// probe --help</p>')
            ->and(TerminalCoreProbeCommand::$received)->toBe([]);
    })->with(['-h', '--help']);
});

describe('BaseCommand help output', function () {
    it('shows the description stored on the command row', function () {
        TerminalCommand::factory()->create([
            'name' => 'probe',
            'command_class' => TerminalCoreProbeCommand::class,
            'description' => 'Probe the terminal',
        ]);

        $html = (new TerminalCoreProbeCommand)->handle('--help')->html;

        expect($html)
            ->toContain('<div class="t-block">')
            ->toContain('<p class="t-paragraph">Probe the terminal</p>');
    });

    it('escapes the description', function () {
        TerminalCommand::factory()->create([
            'name' => 'probe',
            'command_class' => TerminalCoreProbeCommand::class,
            'description' => '<script>alert(1)</script>',
        ]);

        $html = (new TerminalCoreProbeCommand)->handle('-h')->html;

        expect($html)->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')->not->toContain('<script>');
    });

    it('omits the description when the command has no database row', function () {
        $html = (new TerminalCoreProbeCommand)->handle('-h')->html;

        expect($html)->toContain('// probe --help')->not->toContain('t-paragraph');
    });

    it('omits the options block when the command declares none', function () {
        expect((new TerminalCoreProbeCommand)->handle('-h')->html)->not->toContain('t-help-rows');
    });

    it('lists the declared options', function () {
        TerminalCoreProbeCommand::$options = [
            ['option' => 'probe -x', 'description' => 'Do the x thing'],
            ['option' => 'probe -y', 'description' => 'Do the y thing'],
        ];

        $html = (new TerminalCoreProbeCommand)->handle('-h')->html;

        expect($html)
            ->toContain('<div class="t-help-rows">')
            ->toContain('<span class="t-cmd">probe -x</span>')
            ->toContain('<span>Do the x thing</span>')
            ->toContain('<span class="t-cmd">probe -y</span>')
            ->toContain('<span>Do the y thing</span>');
    });

    it('escapes the options', function () {
        TerminalCoreProbeCommand::$options = [['option' => 'probe <n>', 'description' => 'a & b']];

        $html = (new TerminalCoreProbeCommand)->handle('-h')->html;

        expect($html)->toContain('probe &lt;n&gt;')->toContain('a &amp; b')->not->toContain('<n>');
    });
});

describe('BaseCommand::responseUnknownOption', function () {
    it('is available to commands and escapes what the visitor typed', function () {
        $command = new class extends BaseCommand
        {
            public function name(): string
            {
                return 'anon';
            }

            protected function execute(?string $arg): TerminalResponse
            {
                return $this->responseUnknownOption($arg ?? '');
            }
        };

        $response = $command->handle('<i>bad</i>');

        expect($response->type)->toBe('echo')
            ->and($response->html)->toContain('unknown option')
            ->and($response->html)->toContain('&lt;i&gt;bad&lt;/i&gt;')
            ->and($response->html)->not->toContain('<i>bad');
    });
});
