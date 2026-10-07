<?php

use App\Enums\NavItemType;
use App\Models\NavItem;
use App\Models\TerminalCommand;

describe('getDisplayLabel()', function () {
    it('uses the terminal command label for a command button', function () {
        $command = TerminalCommand::factory()->create(['display_label' => 'experience']);
        $item = NavItem::factory()->create([
            'type' => NavItemType::Command,
            'terminal_command_id' => $command->id,
            'label' => 'ignored manual label',
        ]);

        expect($item->getDisplayLabel())->toBe('experience');
    });

    it('is empty for a command button whose command is gone', function () {
        $item = NavItem::factory()->create(['type' => NavItemType::Command, 'terminal_command_id' => null, 'label' => 'manual']);

        expect($item->getDisplayLabel())->toBe('');
    });

    it('uses the manual label for a link button', function () {
        $item = NavItem::factory()->create(['type' => NavItemType::Link, 'label' => 'GitHub']);

        expect($item->getDisplayLabel())->toBe('GitHub');
    });

    it('is empty for a link button without a label', function () {
        $item = NavItem::factory()->create(['type' => NavItemType::Link, 'label' => null]);

        expect($item->getDisplayLabel())->toBe('');
    });

    it('uses the manual label for the CV button', function () {
        $item = NavItem::factory()->create(['type' => NavItemType::Cv, 'label' => 'resume']);

        expect($item->getDisplayLabel())->toBe('resume');
    });

    it('falls back to "cv" for a CV button without a label', function () {
        $item = NavItem::factory()->create(['type' => NavItemType::Cv, 'label' => null]);

        expect($item->getDisplayLabel())->toBe('cv');
    });
});

describe('terminalCommand relation', function () {
    it('belongs to a terminal command', function () {
        $command = TerminalCommand::factory()->create();
        $item = NavItem::factory()->create(['type' => NavItemType::Command, 'terminal_command_id' => $command->id]);

        expect($item->terminalCommand)->toBeInstanceOf(TerminalCommand::class)
            ->and($item->terminalCommand->is($command))->toBeTrue();
    });

    it('is null for items that are not tied to a command', function () {
        expect(NavItem::factory()->create()->terminalCommand)->toBeNull();
    });

    it('is cleared when the terminal command is deleted', function () {
        $command = TerminalCommand::factory()->create();
        $item = NavItem::factory()->create(['type' => NavItemType::Command, 'terminal_command_id' => $command->id]);

        $command->delete();

        expect($item->fresh()->terminal_command_id)->toBeNull()
            ->and($item->fresh()->terminalCommand)->toBeNull();
    });
});

describe('attributes', function () {
    it('casts the type to the NavItemType enum', function () {
        $item = NavItem::factory()->create(['type' => 'cv'])->fresh();

        expect($item->type)->toBe(NavItemType::Cv);
    });

    it('defaults to opening in the same tab and being active', function () {
        $item = NavItem::query()->create(['label' => 'x', 'type' => NavItemType::Link])->fresh();

        expect($item->target)->toBe('_self')
            ->and($item->is_active)->toBeTrue();
    });
});
