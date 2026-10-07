<?php

use App\Terminal\Commands\SudoCommand;

require_once __DIR__.'/Support.php';

beforeEach(fn () => seedSystem());

describe('sudo', function () {
    it('is named sudo and hidden from help', function () {
        $command = new SudoCommand;

        expect($command->name())->toBe('sudo')
            ->and($command->helpGroup())->toBeNull();
    });

    it('denies permission', function () {
        $response = terminalCoreRun('sudo');

        expect($response['type'])->toBe('echo')
            ->and($response['html'])->toContain('Permission denied.')
            ->and($response['html'])->toContain('Nice try.');
    });

    it('denies permission whatever it is asked to run', function () {
        expect(terminalCoreHtml('sudo', 'rm -rf /'))->toContain('Permission denied.');
    });

    it('does not echo what was typed, so there is nothing to inject', function () {
        $html = terminalCoreHtml('sudo', '<script>alert(1)</script>');

        expect($html)->not->toContain('script')->not->toContain('alert');
    });

    it('answers --help with its description', function () {
        expect(terminalCoreHtml('sudo', '--help'))
            ->toContain('// sudo --help')
            ->toContain('Run a command as root');
    });
});
