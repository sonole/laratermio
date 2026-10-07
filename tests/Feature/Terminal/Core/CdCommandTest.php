<?php

use App\Enums\SettingKey;
use App\Terminal\Commands\CdCommand;
use App\Terminal\TerminalContext;

require_once __DIR__.'/Support.php';

beforeEach(fn () => seedSystem());

describe('cd', function () {
    it('is named cd and listed in the system help group', function () {
        $command = new CdCommand(new TerminalContext);

        expect($command->name())->toBe('cd')
            ->and($command->helpGroup())->toBe('system');
    });

    it('goes home when called without a directory', function () {
        app(TerminalContext::class)->setCwd('~/projects');

        $response = terminalCoreRun('cd');

        expect($response['type'])->toBe('cd')
            ->and($response['path'])->toBe('~')
            ->and(app(TerminalContext::class)->getCwd())->toBe('~');
    });

    it('goes home for an empty argument or a lone tilde', function (string $arg) {
        app(TerminalContext::class)->setCwd('~/skills');

        expect(terminalCoreRun('cd', $arg)['path'])->toBe('~')
            ->and(app(TerminalContext::class)->getCwd())->toBe('~');
    })->with(['', '~']);

    it('enters each virtual directory', function (string $dir) {
        $response = terminalCoreRun('cd', $dir);

        expect($response['type'])->toBe('cd')
            ->and($response['path'])->toBe('~/'.$dir)
            ->and(app(TerminalContext::class)->getCwd())->toBe('~/'.$dir);
    })->with(TerminalContext::FILESYSTEM_ROOTS);

    it('accepts the ~/dir and /dir spellings', function (string $arg) {
        expect(terminalCoreRun('cd', $arg)['path'])->toBe('~/skills');
    })->with(['~/skills', '/skills']);

    it('can jump between directories without going home first', function () {
        app(TerminalContext::class)->setCwd('~/projects');

        expect(terminalCoreRun('cd', 'contact')['path'])->toBe('~/contact');
    });

    it('goes back up to the home directory with ..', function () {
        app(TerminalContext::class)->setCwd('~/experience');

        $response = terminalCoreRun('cd', '..');

        expect($response['type'])->toBe('cd')
            ->and($response['path'])->toBe('~')
            ->and(app(TerminalContext::class)->getCwd())->toBe('~');
    });

    it('refuses to go above the home directory', function () {
        $response = terminalCoreRun('cd', '..');

        expect($response['type'])->toBe('echo')
            ->and($response['html'])->toContain('already at home directory.')
            ->and(app(TerminalContext::class)->getCwd())->toBe('~');
    });

    it('rejects a directory that does not exist and stays where it is', function () {
        app(TerminalContext::class)->setCwd('~/skills');

        $response = terminalCoreRun('cd', 'secrets');

        expect($response['type'])->toBe('echo')
            ->and($response['html'])->toContain('cd: secrets: no such directory')
            ->and(app(TerminalContext::class)->getCwd())->toBe('~/skills');
    });

    it('is case sensitive about directory names', function () {
        expect(terminalCoreRun('cd', 'Projects')['type'])->toBe('echo');
    });

    it('does not follow nested paths', function () {
        expect(terminalCoreRun('cd', 'projects/laratermio')['type'])->toBe('echo');
    });

    it('escapes an unknown directory in the error', function () {
        $html = terminalCoreHtml('cd', '<script>alert(1)</script>');

        expect($html)
            ->toContain('cd: &lt;script&gt;alert(1)&lt;/script&gt;: no such directory')
            ->not->toContain('<script>');
    });

    it('returns a prompt that shows the new directory', function () {
        $response = terminalCoreRun('cd', 'projects');

        expect($response['html'])
            ->toContain('visitor')
            ->toContain('localhost')
            ->toContain(':~/projects$');
    });

    it('builds the prompt from the prompt settings', function () {
        terminalCoreSetting(SettingKey::PromptUsername, 'alex');
        terminalCoreSetting(SettingKey::PromptHostname, 'laptop');

        $html = terminalCoreHtml('cd', 'skills');

        expect($html)->toContain('[[b;#4ade80;]alex]')->toContain('[[b;#60a5fa;]laptop]')->toContain(':~/skills$');
    });

    it('answers --help without moving', function () {
        app(TerminalContext::class)->setCwd('~/skills');

        $response = terminalCoreRun('cd', '--help');

        expect($response['type'])->toBe('echo')
            ->and($response['html'])->toContain('// cd --help')
            ->and(app(TerminalContext::class)->getCwd())->toBe('~/skills');
    });
});
