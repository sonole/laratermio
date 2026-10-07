<?php

use App\Terminal\Commands\ClearCommand;

require_once __DIR__.'/Support.php';

beforeEach(fn () => seedSystem());

describe('clear', function () {
    it('is named clear and listed in the system help group', function () {
        $command = new ClearCommand;

        expect($command->name())->toBe('clear')
            ->and($command->helpGroup())->toBe('system');
    });

    it('asks the terminal to clear the screen', function () {
        expect(terminalCoreRun('clear'))->toBe(['type' => 'clear']);
    });

    it('rejects any argument', function () {
        $html = terminalCoreHtml('clear', 'all');

        expect(terminalCoreRun('clear', 'all')['type'])->toBe('echo')
            ->and($html)->toContain('unknown option: <strong>all</strong>');
    });

    it('escapes the rejected argument', function () {
        $html = terminalCoreHtml('clear', '<script>alert(1)</script>');

        expect($html)->toContain('&lt;script&gt;')->not->toContain('<script>');
    });

    it('answers --help with its description', function () {
        $html = terminalCoreHtml('clear', '--help');

        expect($html)->toContain('// clear --help')->toContain('Clear the terminal screen');
    });
});
