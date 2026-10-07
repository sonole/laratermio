<?php

use App\Terminal\Commands\HistoryCommand;

require_once __DIR__.'/Support.php';

beforeEach(fn () => seedSystem());

describe('history', function () {
    it('is named history and listed in the system help group', function () {
        $command = new HistoryCommand;

        expect($command->name())->toBe('history')
            ->and($command->helpGroup())->toBe('system');
    });

    it('asks the browser to show its own command history', function () {
        expect(terminalCoreRun('history'))->toBe(['type' => 'client_history']);
    });

    it('ignores any argument', function () {
        expect(terminalCoreRun('history', 'whatever'))->toBe(['type' => 'client_history']);
    });

    it('answers --help with its description', function () {
        expect(terminalCoreHtml('history', '-h'))
            ->toContain('// history --help')
            ->toContain('Show command history');
    });
});
