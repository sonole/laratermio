<?php

use App\Models\TerminalCommand;
use App\Terminal\Commands\HelpCommand;
use App\Terminal\Commands\WhoamiCommand;

require_once __DIR__.'/Support.php';

beforeEach(fn () => seedSystem());

describe('help', function () {
    it('is named help and hidden from its own listing', function () {
        $command = new HelpCommand;

        expect($command->name())->toBe('help')
            ->and($command->helpGroup())->toBeNull();
    });

    it('prints a help header and one section per command group', function () {
        $response = terminalCoreRun('help');

        expect($response['type'])->toBe('echo')
            ->and($response['html'])->toContain('<p class="t-header">// help</p>')
            ->and($response['html'])->toContain('<p class="t-help-group">explore</p>')
            ->and($response['html'])->toContain('<p class="t-help-group">experience</p>')
            ->and($response['html'])->toContain('<p class="t-help-group">projects</p>')
            ->and($response['html'])->toContain('<p class="t-help-group">system</p>');
    });

    it('lists the commands with their labels and descriptions', function () {
        $html = terminalCoreHtml('help');

        expect($html)
            ->toContain('<span class="t-cmd">about</span>')
            ->toContain('Who I am and what drives me')
            ->toContain('<span class="t-cmd">whoami</span>')
            ->toContain('Print current user identity')
            ->toContain('<span class="t-cmd">clear</span>');
    });

    it('shows the display label, escaped, rather than the bare command name', function () {
        $html = terminalCoreHtml('help');

        expect($html)->toContain('<span class="t-cmd">open &lt;name&gt;</span>')
            ->and($html)->toContain('<span class="t-cmd">cd &lt;dir&gt;</span>');
    });

    it('lists the extra options a command declares', function () {
        $html = terminalCoreHtml('help');

        expect($html)
            ->toContain('<span class="t-cmd">experience -a</span>')
            ->toContain('Full work history at once');
    });

    it('leaves out commands that opt out of help', function (string $hidden) {
        expect(terminalCoreHtml('help'))->not->toContain('<span class="t-cmd">'.$hidden.'</span>');
    })->with(['help', 'sudo', 'cmatrix', 'ping', 'fastfetch']);

    it('puts the groups in a fixed order', function () {
        $html = terminalCoreHtml('help');

        $positions = array_map(
            fn (string $group) => strpos($html, '<p class="t-help-group">'.$group.'</p>'),
            ['explore', 'experience', 'projects', 'system'],
        );

        expect($positions)->toBe(collect($positions)->sort()->values()->all());
    });

    it('leaves out inactive commands', function () {
        TerminalCommand::query()->where('name', 'whoami')->update(['is_active' => false]);

        $html = terminalCoreHtml('help');

        expect($html)->not->toContain('<span class="t-cmd">whoami</span>')
            ->and($html)->toContain('<span class="t-cmd">clear</span>');
    });

    it('skips rows whose command class is missing', function () {
        TerminalCommand::factory()->create([
            'name' => 'ghost',
            'command_class' => 'App\\Terminal\\Commands\\DoesNotExist',
            'display_label' => 'ghost-label',
        ]);

        $html = terminalCoreHtml('help');

        expect($html)->not->toContain('ghost-label')->toContain('<span class="t-cmd">about</span>');
    });

    it('escapes labels and descriptions coming from the database', function () {
        TerminalCommand::factory()->create([
            'name' => 'extra',
            'command_class' => WhoamiCommand::class,
            'display_label' => '<img src=x>',
            'description' => '<script>alert(1)</script>',
        ]);

        $html = terminalCoreHtml('help');

        expect($html)
            ->toContain('&lt;img src=x&gt;')
            ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')
            ->not->toContain('<img src=x>')
            ->not->toContain('<script>');
    });

    it('rejects any argument', function () {
        expect(terminalCoreHtml('help', 'me'))->toContain('unknown option: <strong>me</strong>');
    });

    it('escapes the rejected argument', function () {
        $html = terminalCoreHtml('help', '<b>me</b>');

        expect($html)->toContain('&lt;b&gt;me&lt;/b&gt;')->not->toContain('<b>me');
    });

    it('answers --help with its own help page instead of the command list', function () {
        $html = terminalCoreHtml('help', '--help');

        expect($html)
            ->toContain('// help --help')
            ->toContain('Show available commands')
            ->not->toContain('t-help-group');
    });
});
