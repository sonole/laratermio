<?php

use App\Terminal\Commands\CMatrixCommand;

require_once __DIR__.'/Support.php';

beforeEach(fn () => seedSystem());

describe('cmatrix', function () {
    it('is named cmatrix and hidden from help', function () {
        $command = new CMatrixCommand;

        expect($command->name())->toBe('cmatrix')
            ->and($command->helpGroup())->toBeNull();
    });

    it('opens the cmatrix overlay', function () {
        expect(terminalCoreRun('cmatrix'))->toBe(['type' => 'overlay', 'key' => 'cmatrix']);
    });

    it('ignores any argument', function () {
        expect(terminalCoreRun('cmatrix', '<b>fast</b>'))->toBe(['type' => 'overlay', 'key' => 'cmatrix']);
    });

    it('answers --help instead of opening the overlay', function () {
        $response = terminalCoreRun('cmatrix', '--help');

        expect($response['type'])->toBe('echo')
            ->and($response['html'])->toContain('// cmatrix --help')
            ->and($response['html'])->toContain('Enter the Matrix');
    });
});
