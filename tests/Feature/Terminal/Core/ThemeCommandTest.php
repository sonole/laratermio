<?php

use App\Terminal\Commands\ThemeCommand;

require_once __DIR__.'/Support.php';

beforeEach(fn () => seedSystem());

describe('theme', function () {
    it('is named theme and listed in the system help group', function () {
        $command = new ThemeCommand;

        expect($command->name())->toBe('theme')
            ->and($command->helpGroup())->toBe('system');
    });

    it('switches to a valid mode', function (string $mode) {
        expect(terminalCoreRun('theme', $mode))->toBe(['type' => 'theme', 'key' => $mode]);
    })->with(['light', 'dark', 'system']);

    it('rejects an unknown mode', function () {
        $response = terminalCoreRun('theme', 'solarized');

        expect($response['type'])->toBe('echo')
            ->and($response['html'])->toContain('unknown option: <strong>solarized</strong>');
    });

    it('only accepts the exact lowercase mode names', function (string $mode) {
        expect(terminalCoreRun('theme', $mode)['type'])->toBe('echo');
    })->with(['DARK', 'Light', ' dark', 'dark ', 'dark light', '']);

    it('escapes an unknown mode', function () {
        $html = terminalCoreHtml('theme', '<script>alert(1)</script>');

        expect($html)->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')->not->toContain('<script>');
    });

    it('prints an options block under a theme header when called without a mode', function () {
        $response = terminalCoreRun('theme');

        expect($response['type'])->toBe('echo')
            ->and($response['html'])->toContain('<p class="t-header">// theme</p>')
            ->and($response['html'])->toContain('<div class="t-help-rows">');
    });

    it('lists the light, dark and system modes when called without a mode', function () {
        $html = terminalCoreHtml('theme');

        expect($html)->toContain('light')->toContain('dark')->toContain('system');
    });

    it('answers --help with its description', function () {
        expect(terminalCoreHtml('theme', '--help'))
            ->toContain('// theme --help')
            ->toContain('Switch color scheme');
    });
});
