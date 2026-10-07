<?php

use App\Enums\InteractionType;
use App\Filament\Resources\TerminalCommands\Pages\EditTerminalCommand;
use App\Filament\Resources\TerminalCommands\Pages\ListTerminalCommands;
use App\Filament\Resources\TerminalCommands\TerminalCommandResource;
use App\Models\NavItem;
use App\Models\TerminalCommand;
use App\Terminal\CommandRegistry;
use App\Terminal\Commands\ExperienceCommand;
use App\Terminal\Commands\ProjectsCommand;
use App\Terminal\Commands\WhoamiCommand;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

describe('access', function () {
    it('redirects guests to the admin login', function () {
        $command = TerminalCommand::factory()->create();

        $this->get(TerminalCommandResource::getUrl('index'))->assertRedirect('/admin/login');
        $this->get(TerminalCommandResource::getUrl('edit', ['record' => $command]))->assertRedirect('/admin/login');
    });

    it('lets an admin open the list and edit pages', function () {
        actingAsAdmin();
        $command = TerminalCommand::factory()->create();

        $this->get(TerminalCommandResource::getUrl('index'))->assertOk();
        $this->get(TerminalCommandResource::getUrl('edit', ['record' => $command]))->assertOk();
    });
});

describe('edit-only resource', function () {
    beforeEach(fn () => actingAsAdmin());

    it('has no create page', function () {
        expect(array_keys(TerminalCommandResource::getPages()))->toBe(['index', 'edit']);

        $this->get('/admin/terminal-commands/create')->assertNotFound();
    });

    it('offers no create button, no bulk delete and no reordering', function () {
        TerminalCommand::factory()->create();

        Livewire::test(ListTerminalCommands::class)
            ->assertActionDoesNotExist('create')
            ->assertActionDoesNotExist(TestAction::make('delete')->table()->bulk())
            ->assertSet('isTableReordering', false)
            ->assertTableColumnDoesNotExist('sort_order');
    });
});

describe('list', function () {
    beforeEach(fn () => actingAsAdmin());

    it('lists the registered commands, active or not', function () {
        $active = TerminalCommand::factory()->create();
        $inactive = TerminalCommand::factory()->inactive()->create();

        Livewire::test(ListTerminalCommands::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$active, $inactive])
            ->assertCountTableRecords(2);
    });

    it('shows the columns and an edit action per row', function () {
        $command = TerminalCommand::factory()->create();

        Livewire::test(ListTerminalCommands::class)
            ->assertTableColumnExists('name')
            ->assertTableColumnExists('display_label')
            ->assertTableColumnExists('description')
            ->assertTableColumnExists('interaction_type')
            ->assertTableColumnExists('is_active')
            ->assertActionExists(TestAction::make('edit')->table($command));
    });

    it('shows the interaction type of each command', function () {
        $plain = TerminalCommand::factory()->create(['interaction_type' => null]);
        $paginate = TerminalCommand::factory()->create(['command_class' => ExperienceCommand::class, 'interaction_type' => InteractionType::Paginate]);
        $selector = TerminalCommand::factory()->create(['command_class' => ProjectsCommand::class, 'interaction_type' => InteractionType::Selector]);

        Livewire::test(ListTerminalCommands::class)
            ->assertTableColumnFormattedStateSet('interaction_type', '', $plain)
            ->assertTableColumnFormattedStateSet('interaction_type', 'Paginate (one at a time)', $paginate)
            ->assertTableColumnFormattedStateSet('interaction_type', 'Selector (arrow keys)', $selector);
    });

    it('toggles the active state from the table', function () {
        $command = TerminalCommand::factory()->create();

        Livewire::test(ListTerminalCommands::class)
            ->call('updateTableColumnState', 'is_active', (string) $command->getKey(), false);

        expect($command->fresh()->is_active)->toBeFalse();
    });

    it('stops resolving a command in the terminal once it is switched off', function () {
        seedSystem();
        $help = TerminalCommand::query()->where('name', 'help')->firstOrFail();

        Livewire::test(ListTerminalCommands::class)
            ->call('updateTableColumnState', 'is_active', (string) $help->getKey(), false);

        expect(app(CommandRegistry::class)->resolve('help'))->toBeNull();
    });
});

describe('edit', function () {
    beforeEach(fn () => actingAsAdmin());

    it('prefills the form', function () {
        $command = TerminalCommand::factory()->create([
            'name' => 'whoami',
            'display_label' => 'who am i',
            'command_class' => WhoamiCommand::class,
            'description' => 'About the owner',
            'is_active' => false,
        ]);

        Livewire::test(EditTerminalCommand::class, ['record' => $command->getKey()])
            ->assertFormSet([
                'name' => 'whoami',
                'display_label' => 'who am i',
                'command_class' => WhoamiCommand::class,
                'description' => 'About the owner',
                'is_active' => false,
            ]);
    });

    it('saves changes', function () {
        $command = TerminalCommand::factory()->create(['name' => 'old', 'display_label' => 'old', 'description' => null]);

        Livewire::test(EditTerminalCommand::class, ['record' => $command->getKey()])
            ->fillForm([
                'name' => 'fresh',
                'display_label' => 'fresh label',
                'description' => 'Brand new description',
                'is_active' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $command->refresh();

        expect($command->name)->toBe('fresh')
            ->and($command->display_label)->toBe('fresh label')
            ->and($command->description)->toBe('Brand new description')
            ->and($command->is_active)->toBeFalse();
    });

    it('requires a name, label and handler class', function () {
        $command = TerminalCommand::factory()->create(['name' => 'keep']);

        Livewire::test(EditTerminalCommand::class, ['record' => $command->getKey()])
            ->fillForm(['name' => '', 'display_label' => '', 'command_class' => ''])
            ->call('save')
            ->assertHasFormErrors(['name' => 'required', 'display_label' => 'required', 'command_class' => 'required']);

        expect($command->fresh()->name)->toBe('keep');
    });

    it('only offers the interaction type for commands with structured data', function () {
        $plain = TerminalCommand::factory()->create(['command_class' => WhoamiCommand::class]);
        $structured = TerminalCommand::factory()->create(['command_class' => ProjectsCommand::class, 'interaction_type' => InteractionType::Selector]);

        Livewire::test(EditTerminalCommand::class, ['record' => $plain->getKey()])
            ->assertFormFieldHidden('interaction_type');

        Livewire::test(EditTerminalCommand::class, ['record' => $structured->getKey()])
            ->assertFormFieldVisible('interaction_type')
            ->assertFormSet(['interaction_type' => 'selector']);
    });

    it('changes the interaction type of a structured command', function () {
        $command = TerminalCommand::factory()->create(['command_class' => ExperienceCommand::class, 'interaction_type' => InteractionType::Paginate]);

        Livewire::test(EditTerminalCommand::class, ['record' => $command->getKey()])
            ->fillForm(['interaction_type' => 'selector'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($command->fresh()->interaction_type)->toBe(InteractionType::Selector);
    });

    it('rejects an unknown interaction type', function () {
        $command = TerminalCommand::factory()->create(['command_class' => ExperienceCommand::class, 'interaction_type' => InteractionType::Paginate]);

        Livewire::test(EditTerminalCommand::class, ['record' => $command->getKey()])
            ->fillForm(['interaction_type' => 'bogus'])
            ->call('save')
            ->assertHasFormErrors(['interaction_type']);

        expect($command->fresh()->interaction_type)->toBe(InteractionType::Paginate);
    });

    it('rejects a name that another command already uses', function () {
        TerminalCommand::factory()->create(['name' => 'taken']);
        $command = TerminalCommand::factory()->create(['name' => 'mine']);

        Livewire::test(EditTerminalCommand::class, ['record' => $command->getKey()])
            ->fillForm(['name' => 'taken'])
            ->call('save')
            ->assertHasFormErrors(['name']);
    });

    it('deletes the command from the header action and keeps its nav items', function () {
        $command = TerminalCommand::factory()->create();
        $navItem = NavItem::factory()->create(['terminal_command_id' => $command->id]);

        Livewire::test(EditTerminalCommand::class, ['record' => $command->getKey()])
            ->callAction(DeleteAction::class)
            ->assertNotified()
            ->assertRedirect();

        expect(TerminalCommand::query()->count())->toBe(0)
            ->and($navItem->fresh()->terminal_command_id)->toBeNull();
    });
});
