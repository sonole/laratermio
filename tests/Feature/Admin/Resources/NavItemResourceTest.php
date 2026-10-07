<?php

use App\Enums\NavItemType;
use App\Filament\Resources\NavItems\NavItemResource;
use App\Filament\Resources\NavItems\Pages\CreateNavItem;
use App\Filament\Resources\NavItems\Pages\EditNavItem;
use App\Filament\Resources\NavItems\Pages\ListNavItems;
use App\Models\NavItem;
use App\Models\TerminalCommand;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

describe('access', function () {
    it('redirects guests to the admin login', function () {
        $item = NavItem::factory()->create();

        $this->get(NavItemResource::getUrl('index'))->assertRedirect('/admin/login');
        $this->get(NavItemResource::getUrl('create'))->assertRedirect('/admin/login');
        $this->get(NavItemResource::getUrl('edit', ['record' => $item]))->assertRedirect('/admin/login');
    });

    it('lets an admin open the pages', function () {
        actingAsAdmin();
        $item = NavItem::factory()->create();

        $this->get(NavItemResource::getUrl('index'))->assertOk();
        $this->get(NavItemResource::getUrl('create'))->assertOk();
        $this->get(NavItemResource::getUrl('edit', ['record' => $item]))->assertOk();
    });
});

describe('list', function () {
    beforeEach(fn () => actingAsAdmin());

    it('shows the records, active or not', function () {
        $active = NavItem::factory()->create();
        $inactive = NavItem::factory()->inactive()->create();

        Livewire::test(ListNavItems::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$active, $inactive])
            ->assertCountTableRecords(2);
    });

    it('resolves the label of each button type', function () {
        $command = TerminalCommand::factory()->create(['display_label' => 'about me']);
        $commandItem = NavItem::factory()->create(['type' => NavItemType::Command, 'terminal_command_id' => $command->id, 'label' => null]);
        $linkItem = NavItem::factory()->create(['type' => NavItemType::Link, 'label' => 'GitHub']);
        $cvItem = NavItem::factory()->create(['type' => NavItemType::Cv, 'label' => null]);

        Livewire::test(ListNavItems::class)
            ->assertTableColumnStateSet('label', 'about me', $commandItem)
            ->assertTableColumnStateSet('label', 'GitHub', $linkItem)
            ->assertTableColumnStateSet('label', 'cv', $cvItem);
    });

    it('shows the button type as a badge', function () {
        $command = NavItem::factory()->create(['type' => NavItemType::Command]);
        $link = NavItem::factory()->create(['type' => NavItemType::Link]);
        $cv = NavItem::factory()->create(['type' => NavItemType::Cv]);

        Livewire::test(ListNavItems::class)
            ->assertTableColumnFormattedStateSet('type', 'Command button', $command)
            ->assertTableColumnFormattedStateSet('type', 'Link button', $link)
            ->assertTableColumnFormattedStateSet('type', 'CV button', $cv);
    });

    it('orders by sort order by default', function () {
        $second = NavItem::factory()->create(['sort_order' => 2]);
        $first = NavItem::factory()->create(['sort_order' => 1]);
        $third = NavItem::factory()->create(['sort_order' => 3]);

        Livewire::test(ListNavItems::class)
            ->assertCanSeeTableRecords([$first, $second, $third], inOrder: true)
            ->sortTable('sort_order', 'desc')
            ->assertCanSeeTableRecords([$third, $second, $first], inOrder: true);
    });

    it('toggles the active state from the table', function () {
        $item = NavItem::factory()->create();

        Livewire::test(ListNavItems::class)
            ->call('updateTableColumnState', 'is_active', (string) $item->getKey(), false);

        expect($item->fresh()->is_active)->toBeFalse();
    });

    it('can be reordered by sort order', function () {
        $a = NavItem::factory()->create(['sort_order' => 1]);
        $b = NavItem::factory()->create(['sort_order' => 2]);
        $c = NavItem::factory()->create(['sort_order' => 3]);

        Livewire::test(ListNavItems::class)->call('reorderTable', [$b->getKey(), $c->getKey(), $a->getKey()]);

        expect(NavItem::query()->ordered()->pluck('id')->all())->toBe([$b->id, $c->id, $a->id]);
    });

    it('deletes records in bulk', function () {
        $items = NavItem::factory()->count(3)->create();

        Livewire::test(ListNavItems::class)
            ->selectTableRecords($items->take(2)->pluck('id')->all())
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertNotified();

        expect(NavItem::query()->count())->toBe(1);
    });
});

describe('create', function () {
    beforeEach(function () {
        actingAsAdmin();

        $root = storage_path('framework/testing/disks/admin-resources');
        config(['filesystems.disks.public.root' => $root]);
        Storage::forgetDisk('public');
    });

    afterEach(function () {
        File::deleteDirectory(storage_path('framework/testing/disks/admin-resources'));
    });

    it('defaults to an active command button opening in a new tab', function () {
        Livewire::test(CreateNavItem::class)
            ->assertFormSet([
                'type' => 'command',
                'is_active' => true,
                'sort_order' => 0,
                'target' => '_blank',
            ]);
    });

    it('persists a command button', function () {
        $command = TerminalCommand::factory()->create(['display_label' => 'projects']);

        Livewire::test(CreateNavItem::class)
            ->fillForm([
                'type' => 'command',
                'terminal_command_id' => $command->id,
                'command_args' => '-a',
                'sort_order' => 4,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $item = NavItem::query()->firstOrFail();

        expect($item->type)->toBe(NavItemType::Command)
            ->and($item->terminal_command_id)->toBe($command->id)
            ->and($item->command_args)->toBe('-a')
            ->and($item->sort_order)->toBe(4)
            ->and($item->is_active)->toBeTrue()
            ->and($item->getDisplayLabel())->toBe('projects');
    });

    it('requires a command for a command button', function () {
        Livewire::test(CreateNavItem::class)
            ->fillForm(['type' => 'command', 'terminal_command_id' => null])
            ->call('create')
            ->assertHasFormErrors(['terminal_command_id' => 'required']);

        expect(NavItem::query()->count())->toBe(0);
    });

    it('persists a link button with an external url', function () {
        Livewire::test(CreateNavItem::class)
            ->fillForm([
                'type' => 'link',
                'label' => 'GitHub',
                'url' => 'https://github.com/alex',
                'target' => '_self',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $item = NavItem::query()->firstOrFail();

        expect($item->type)->toBe(NavItemType::Link)
            ->and($item->label)->toBe('GitHub')
            ->and($item->url)->toBe('https://github.com/alex')
            ->and($item->target)->toBe('_self')
            ->and($item->terminal_command_id)->toBeNull();
    });

    it('requires a label for a link button', function () {
        Livewire::test(CreateNavItem::class)
            ->fillForm(['type' => 'link', 'label' => null, 'url' => 'https://example.com'])
            ->call('create')
            ->assertHasFormErrors(['label' => 'required']);

        expect(NavItem::query()->count())->toBe(0);
    });

    it('persists a cv button without a label', function () {
        Livewire::test(CreateNavItem::class)
            ->fillForm(['type' => 'cv', 'label' => null, 'target' => '_blank'])
            ->call('create')
            ->assertHasNoFormErrors();

        $item = NavItem::query()->firstOrFail();

        expect($item->type)->toBe(NavItemType::Cv)
            ->and($item->getDisplayLabel())->toBe('cv');
    });

    it('requires a valid type', function () {
        Livewire::test(CreateNavItem::class)
            ->fillForm(['type' => null])
            ->call('create')
            ->assertHasFormErrors(['type' => 'required']);

        Livewire::test(CreateNavItem::class)
            ->fillForm(['type' => 'bogus'])
            ->call('create')
            ->assertHasFormErrors(['type']);
    });

    it('requires a valid target for link and cv buttons', function () {
        Livewire::test(CreateNavItem::class)
            ->fillForm(['type' => 'link', 'label' => 'Site', 'target' => null])
            ->call('create')
            ->assertHasFormErrors(['target' => 'required']);

        Livewire::test(CreateNavItem::class)
            ->fillForm(['type' => 'cv', 'target' => '_top'])
            ->call('create')
            ->assertHasFormErrors(['target']);
    });

    it('shows the fields that belong to the chosen type', function () {
        Livewire::test(CreateNavItem::class)
            // Command (the default): only the command and its arguments.
            ->assertFormFieldVisible('terminal_command_id')
            ->assertFormFieldVisible('command_args')
            ->assertFormFieldHidden('label')
            ->assertFormFieldHidden('url')
            ->assertFormFieldHidden('url_source')
            ->assertFormFieldHidden('url_file')
            ->assertFormFieldHidden('target')
            // Link: a label, target and the url (or an uploaded file).
            ->fillForm(['type' => 'link'])
            ->assertFormFieldHidden('terminal_command_id')
            ->assertFormFieldHidden('command_args')
            ->assertFormFieldVisible('label')
            ->assertFormFieldVisible('url_source')
            ->assertFormFieldVisible('url')
            ->assertFormFieldHidden('url_file')
            ->assertFormFieldVisible('target')
            // Cv: a label and target, nothing else.
            ->fillForm(['type' => 'cv'])
            ->assertFormFieldHidden('terminal_command_id')
            ->assertFormFieldHidden('command_args')
            ->assertFormFieldVisible('label')
            ->assertFormFieldHidden('url_source')
            ->assertFormFieldHidden('url')
            ->assertFormFieldHidden('url_file')
            ->assertFormFieldVisible('target');
    });

    it('swaps the url input for a file upload when the url source is a file', function () {
        Livewire::test(CreateNavItem::class)
            ->fillForm(['type' => 'link', 'url_source' => 'file'])
            ->assertFormFieldHidden('url')
            ->assertFormFieldVisible('url_file')
            ->fillForm(['url_source' => 'external'])
            ->assertFormFieldVisible('url')
            ->assertFormFieldHidden('url_file');
    });

    it('stores an uploaded file and links to it', function () {
        $file = UploadedFile::fake()->create('resume.pdf', 20, 'application/pdf');

        Livewire::test(CreateNavItem::class)
            ->fillForm(['type' => 'link', 'label' => 'Resume', 'url_source' => 'file', 'target' => '_blank'])
            ->fillForm(['url_file' => $file])
            ->call('create')
            ->assertHasNoFormErrors();

        $item = NavItem::query()->firstOrFail();

        expect($item->url)->toStartWith('/storage/'.NavItem::UPLOAD_DIRECTORY.'/')
            ->and($item->url)->toEndWith('.pdf');

        Storage::disk('public')->assertExists(ltrim(str_replace('/storage/', '', $item->url), '/'));
    });
});

describe('edit', function () {
    beforeEach(function () {
        actingAsAdmin();

        $root = storage_path('framework/testing/disks/admin-resources');
        config(['filesystems.disks.public.root' => $root]);
        Storage::forgetDisk('public');
    });

    afterEach(function () {
        File::deleteDirectory(storage_path('framework/testing/disks/admin-resources'));
    });

    it('keeps the link to an already uploaded file when other fields change', function () {
        Storage::disk('public')->put(NavItem::UPLOAD_DIRECTORY.'/resume.pdf', 'pdf');
        $item = NavItem::factory()->create(['type' => NavItemType::Link, 'label' => 'Resume', 'url' => '/storage/'.NavItem::UPLOAD_DIRECTORY.'/resume.pdf']);

        Livewire::test(EditNavItem::class, ['record' => $item->getKey()])
            ->assertFormSet(['url_source' => 'file'])
            ->fillForm(['label' => 'My resume'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($item->fresh())
            ->label->toBe('My resume')
            ->url->toBe('/storage/'.NavItem::UPLOAD_DIRECTORY.'/resume.pdf');
    });

    it('saves the typed address when an uploaded file is swapped for an external URL', function () {
        $item = NavItem::factory()->create(['type' => NavItemType::Link, 'label' => 'Resume', 'url' => '/storage/'.NavItem::UPLOAD_DIRECTORY.'/resume.pdf']);

        Livewire::test(EditNavItem::class, ['record' => $item->getKey()])
            ->fillForm(['url_source' => 'external', 'url' => 'https://example.com/cv'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($item->fresh()->url)->toBe('https://example.com/cv');
    });

    it('leaves the url of a command button alone', function () {
        $command = TerminalCommand::factory()->create();
        $item = NavItem::factory()->create(['type' => NavItemType::Command, 'terminal_command_id' => $command->id, 'label' => null, 'url' => null]);

        Livewire::test(EditNavItem::class, ['record' => $item->getKey()])
            ->fillForm(['command_args' => '-a'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($item->fresh())->url->toBeNull()->command_args->toBe('-a');
    });

    it('prefills a command button', function () {
        $command = TerminalCommand::factory()->create();
        $item = NavItem::factory()->create([
            'type' => NavItemType::Command,
            'terminal_command_id' => $command->id,
            'command_args' => '--all',
            'label' => null,
            'url' => null,
            'sort_order' => 7,
            'is_active' => false,
        ]);

        Livewire::test(EditNavItem::class, ['record' => $item->getKey()])
            ->assertFormSet([
                'type' => 'command',
                'terminal_command_id' => $command->id,
                'command_args' => '--all',
                'sort_order' => 7,
                'is_active' => false,
            ])
            ->assertFormFieldVisible('terminal_command_id')
            ->assertFormFieldHidden('label')
            ->assertFormFieldHidden('target');
    });

    it('prefills a link button with an external url', function () {
        $item = NavItem::factory()->create([
            'type' => NavItemType::Link,
            'label' => 'Blog',
            'url' => 'https://blog.example.com',
            'target' => '_self',
        ]);

        Livewire::test(EditNavItem::class, ['record' => $item->getKey()])
            ->assertFormSet([
                'type' => 'link',
                'label' => 'Blog',
                'url' => 'https://blog.example.com',
                'target' => '_self',
                'url_source' => 'external',
            ])
            ->assertFormFieldVisible('url')
            ->assertFormFieldHidden('url_file');
    });

    it('recognises a link to an uploaded file', function () {
        $item = NavItem::factory()->create([
            'type' => NavItemType::Link,
            'label' => 'Resume',
            'url' => '/storage/'.NavItem::UPLOAD_DIRECTORY.'/resume.pdf',
        ]);

        Livewire::test(EditNavItem::class, ['record' => $item->getKey()])
            ->assertFormSet(['url_source' => 'file'])
            ->assertFormFieldVisible('url_file')
            ->assertFormFieldHidden('url');
    });

    it('saves changes to a link button', function () {
        $item = NavItem::factory()->create([
            'type' => NavItemType::Link,
            'label' => 'Old',
            'url' => 'https://old.example.com',
            'target' => '_blank',
        ]);

        Livewire::test(EditNavItem::class, ['record' => $item->getKey()])
            ->fillForm(['label' => 'New', 'url' => 'https://new.example.com', 'target' => '_self', 'is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $item->refresh();

        expect($item->label)->toBe('New')
            ->and($item->url)->toBe('https://new.example.com')
            ->and($item->target)->toBe('_self')
            ->and($item->is_active)->toBeFalse();
    });

    it('switches a link button over to a command button', function () {
        $command = TerminalCommand::factory()->create(['display_label' => 'skills']);
        $item = NavItem::factory()->create(['type' => NavItemType::Link, 'label' => 'Blog', 'url' => 'https://blog.example.com']);

        Livewire::test(EditNavItem::class, ['record' => $item->getKey()])
            ->fillForm(['type' => 'command', 'terminal_command_id' => $command->id])
            ->assertFormFieldVisible('terminal_command_id')
            ->assertFormFieldHidden('label')
            ->call('save')
            ->assertHasNoFormErrors();

        $item->refresh();

        expect($item->type)->toBe(NavItemType::Command)
            ->and($item->terminal_command_id)->toBe($command->id)
            ->and($item->getDisplayLabel())->toBe('skills');
    });

    it('rejects an empty label on a link button', function () {
        $item = NavItem::factory()->create(['type' => NavItemType::Link, 'label' => 'Keep me']);

        Livewire::test(EditNavItem::class, ['record' => $item->getKey()])
            ->fillForm(['label' => ''])
            ->call('save')
            ->assertHasFormErrors(['label' => 'required']);

        expect($item->fresh()->label)->toBe('Keep me');
    });

    it('deletes the record from the header action', function () {
        $item = NavItem::factory()->create();

        Livewire::test(EditNavItem::class, ['record' => $item->getKey()])
            ->callAction(DeleteAction::class)
            ->assertNotified()
            ->assertRedirect();

        expect(NavItem::query()->count())->toBe(0);
    });
});
