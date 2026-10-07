<?php

use App\Models\TerminalCommand;
use App\Terminal\CommandRegistry;
use App\Terminal\Commands\CdCommand;
use App\Terminal\Commands\WhoamiCommand;
use App\Terminal\Contracts\TerminalCommandContract;
use App\Terminal\TerminalResponse;

require_once __DIR__.'/Support.php';

beforeEach(function () {
    TerminalCoreProbeCommand::$received = [];
    TerminalCoreProbeCommand::$options = [];
});

describe('CommandRegistry::resolve', function () {
    it('returns the command class registered in the database row', function () {
        TerminalCommand::factory()->create(['name' => 'who', 'command_class' => WhoamiCommand::class]);

        $command = app(CommandRegistry::class)->resolve('who');

        expect($command)->toBeInstanceOf(WhoamiCommand::class)
            ->and($command)->toBeInstanceOf(TerminalCommandContract::class);
    });

    it('is keyed by the row name, not by the command name()', function () {
        TerminalCommand::factory()->create(['name' => 'alias', 'command_class' => WhoamiCommand::class]);

        expect(app(CommandRegistry::class)->resolve('alias')->name())->toBe('whoami')
            ->and(app(CommandRegistry::class)->resolve('whoami'))->toBeNull();
    });

    it('returns null for an inactive command', function () {
        TerminalCommand::factory()->inactive()->create(['name' => 'who']);

        expect(app(CommandRegistry::class)->resolve('who'))->toBeNull();
    });

    it('returns null for an unknown command', function () {
        expect(app(CommandRegistry::class)->resolve('nope'))->toBeNull();
    });

    it('returns null when the class does not exist', function () {
        TerminalCommand::factory()->create(['name' => 'ghost', 'command_class' => 'App\\Terminal\\Commands\\DoesNotExist']);

        expect(app(CommandRegistry::class)->resolve('ghost'))->toBeNull();
    });

    it('returns null when the class column is empty', function () {
        TerminalCommand::factory()->create(['name' => 'blank', 'command_class' => '']);

        expect(app(CommandRegistry::class)->resolve('blank'))->toBeNull();
    });

    it('builds the command through the container so its dependencies are injected', function () {
        TerminalCommand::factory()->create(['name' => 'cd', 'command_class' => CdCommand::class]);

        expect(app(CommandRegistry::class)->resolve('cd'))->toBeInstanceOf(CdCommand::class);
    });
});

describe('CommandRegistry::dispatch', function () {
    it('runs the command and returns its response', function () {
        TerminalCommand::factory()->create(['name' => 'who', 'command_class' => WhoamiCommand::class]);

        $response = app(CommandRegistry::class)->dispatch('who', null);

        expect($response)->toBeInstanceOf(TerminalResponse::class)
            ->and($response->type)->toBe('echo')
            ->and($response->html)->toContain('visitor');
    });

    it('returns null for an unknown command so the caller can report it', function () {
        expect(app(CommandRegistry::class)->dispatch('nope', null))->toBeNull();
    });

    it('answers an inactive command with a command-not-found message', function () {
        TerminalCommand::factory()->inactive()->create(['name' => 'who', 'command_class' => WhoamiCommand::class]);

        $response = app(CommandRegistry::class)->dispatch('who', null);

        expect($response->type)->toBe('echo')
            ->and($response->html)->toContain('command not found')
            ->and($response->html)->toContain('<strong>who</strong>');
    });

    it('escapes the command name in the inactive-command message', function () {
        TerminalCommand::factory()->inactive()->create(['name' => '<b>x</b>']);

        $html = app(CommandRegistry::class)->dispatch('<b>x</b>', null)->html;

        expect($html)->toContain('&lt;b&gt;x&lt;/b&gt;')->not->toContain('<b>x</b>');
    });

    it('returns null when the registered class does not exist', function () {
        TerminalCommand::factory()->create(['name' => 'ghost', 'command_class' => 'App\\Terminal\\Commands\\DoesNotExist']);

        expect(app(CommandRegistry::class)->dispatch('ghost', null))->toBeNull();
    });

    it('returns null when the class column is empty', function () {
        TerminalCommand::factory()->create(['name' => 'blank', 'command_class' => '']);

        expect(app(CommandRegistry::class)->dispatch('blank', null))->toBeNull();
    });

    it('passes the argument through to the command untouched', function () {
        TerminalCommand::factory()->create(['name' => 'probe', 'command_class' => TerminalCoreProbeCommand::class]);

        $response = app(CommandRegistry::class)->dispatch('probe', 'some  arg <b>');

        expect(TerminalCoreProbeCommand::$received)->toBe(['some  arg <b>'])
            ->and($response->html)->toBe("probe:'some  arg <b>'");
    });

    it('passes null when there is no argument', function () {
        TerminalCommand::factory()->create(['name' => 'probe', 'command_class' => TerminalCoreProbeCommand::class]);

        app(CommandRegistry::class)->dispatch('probe', null);

        expect(TerminalCoreProbeCommand::$received)->toBe([null]);
    });

    it('does not run an inactive command', function () {
        TerminalCommand::factory()->inactive()->create(['name' => 'probe', 'command_class' => TerminalCoreProbeCommand::class]);

        app(CommandRegistry::class)->dispatch('probe', 'x');

        expect(TerminalCoreProbeCommand::$received)->toBe([]);
    });

    it('reflects a command being switched off and on again', function () {
        $row = TerminalCommand::factory()->create(['name' => 'who', 'command_class' => WhoamiCommand::class]);
        $registry = app(CommandRegistry::class);

        $row->update(['is_active' => false]);
        expect($registry->dispatch('who', null)->html)->toContain('command not found');

        $row->update(['is_active' => true]);
        expect($registry->dispatch('who', null)->html)->not->toContain('command not found');
    });
});

describe('the shipped command registry', function () {
    it('registers a resolvable command for every seeded row', function () {
        seedSystem();

        $rows = TerminalCommand::query()->get();

        expect($rows)->not->toBeEmpty();

        foreach ($rows as $row) {
            $command = app(CommandRegistry::class)->resolve($row->name);

            expect($command)->toBeInstanceOf(TerminalCommandContract::class, "row [{$row->name}] does not resolve")
                ->and($command->name())->toBe($row->name);
        }
    });

    it('registers every command the terminal ships with', function () {
        seedSystem();

        expect(TerminalCommand::query()->pluck('name')->all())->toContain(
            'about', 'cd', 'clear', 'cmatrix', 'contact', 'education', 'experience', 'fastfetch',
            'help', 'history', 'ls', 'open', 'ping', 'projects', 'search', 'skills', 'sudo', 'theme', 'whoami',
        );
    });
});
