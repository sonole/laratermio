<?php

use App\Filament\Resources\ContactItems\Pages\CreateContactItem;
use App\Filament\Resources\ContactItems\Pages\EditContactItem;
use App\Filament\Resources\ContactItems\Pages\ListContactItems;
use App\Models\ContactItem;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

describe('access', function () {
    it('redirects guests to the admin login', function (string $uri) {
        $this->get($uri)->assertRedirect('/admin/login');
    })->with(['/admin/contact-items', '/admin/contact-items/create', '/admin/contact-items/1/edit']);

    it('lets an admin open the pages', function () {
        actingAsAdmin();
        $item = ContactItem::factory()->create();

        $this->get('/admin/contact-items')->assertOk();
        $this->get('/admin/contact-items/create')->assertOk();
        $this->get("/admin/contact-items/{$item->id}/edit")->assertOk();
    });
});

describe('list', function () {
    beforeEach(fn () => actingAsAdmin());

    it('shows the records', function () {
        $items = ContactItem::factory()->count(3)->create();

        Livewire::test(ListContactItems::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($items)
            ->assertCountTableRecords(3);
    });

    it('shows active and inactive records alike', function () {
        $active = ContactItem::factory()->create();
        $inactive = ContactItem::factory()->inactive()->create();

        Livewire::test(ListContactItems::class)
            ->assertCanSeeTableRecords([$active, $inactive]);
    });

    it('shows the columns', function () {
        ContactItem::factory()->create();

        Livewire::test(ListContactItems::class)
            ->assertTableColumnExists('label')
            ->assertTableColumnExists('url')
            ->assertTableColumnExists('icon')
            ->assertTableColumnExists('is_active');
    });

    it('orders by sort order by default', function () {
        $second = ContactItem::factory()->create(['sort_order' => 2]);
        $first = ContactItem::factory()->create(['sort_order' => 1]);
        $third = ContactItem::factory()->create(['sort_order' => 3]);

        Livewire::test(ListContactItems::class)
            ->assertCanSeeTableRecords([$first, $second, $third], inOrder: true);
    });

    it('sorts by label', function () {
        $b = ContactItem::factory()->create(['label' => 'Bravo']);
        $a = ContactItem::factory()->create(['label' => 'Alpha']);

        Livewire::test(ListContactItems::class)
            ->sortTable('label')
            ->assertCanSeeTableRecords([$a, $b], inOrder: true)
            ->sortTable('label', 'desc')
            ->assertCanSeeTableRecords([$b, $a], inOrder: true);
    });

    it('searches by label', function () {
        $match = ContactItem::factory()->create(['label' => 'hello@example.com']);
        $other = ContactItem::factory()->create(['label' => 'Athens, Greece']);

        Livewire::test(ListContactItems::class)
            ->searchTable('Athens')
            ->assertCanSeeTableRecords([$other])
            ->assertCanNotSeeTableRecords([$match]);
    });

    it('toggles the active state from the table', function () {
        $item = ContactItem::factory()->create();

        Livewire::test(ListContactItems::class)
            ->assertTableColumnStateSet('is_active', true, $item)
            ->call('updateTableColumnState', 'is_active', (string) $item->getKey(), false);

        expect($item->fresh()->is_active)->toBeFalse();
    });

    it('can be reordered by sort order', function () {
        $a = ContactItem::factory()->create(['sort_order' => 1]);
        $b = ContactItem::factory()->create(['sort_order' => 2]);
        $c = ContactItem::factory()->create(['sort_order' => 3]);

        Livewire::test(ListContactItems::class)
            ->call('reorderTable', [$c->getKey(), $a->getKey(), $b->getKey()]);

        expect(ContactItem::query()->ordered()->pluck('id')->all())->toBe([$c->id, $a->id, $b->id]);
    });

    it('deletes records in bulk', function () {
        $items = ContactItem::factory()->count(3)->create();

        Livewire::test(ListContactItems::class)
            ->selectTableRecords($items->take(2)->pluck('id')->all())
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertNotified();

        expect(ContactItem::query()->count())->toBe(1);
    });
});

describe('create', function () {
    beforeEach(fn () => actingAsAdmin());

    it('persists a valid record', function () {
        Livewire::test(CreateContactItem::class)
            ->fillForm([
                'icon' => 'fa-brands fa-github',
                'label' => 'github.com/alex',
                'url' => 'https://github.com/alex',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $item = ContactItem::query()->firstOrFail();

        expect($item->label)->toBe('github.com/alex')
            ->and($item->icon)->toBe('fa-brands fa-github')
            ->and($item->url)->toBe('https://github.com/alex')
            ->and($item->is_active)->toBeTrue();
    });

    it('defaults to active and allows a plain-text item without icon or url', function () {
        Livewire::test(CreateContactItem::class)
            ->assertFormSet(['is_active' => true])
            ->fillForm(['label' => 'Athens, Greece'])
            ->call('create')
            ->assertHasNoFormErrors();

        $item = ContactItem::query()->firstOrFail();

        expect($item->url)->toBeNull()
            ->and($item->icon)->toBeNull();
    });

    it('requires a label', function () {
        Livewire::test(CreateContactItem::class)
            ->fillForm(['label' => null])
            ->call('create')
            ->assertHasFormErrors(['label' => 'required']);

        expect(ContactItem::query()->count())->toBe(0);
    });

    it('requires the url to be a valid url', function () {
        Livewire::test(CreateContactItem::class)
            ->fillForm(['label' => 'Website', 'url' => 'not a url'])
            ->call('create')
            ->assertHasFormErrors(['url' => 'url']);

        expect(ContactItem::query()->count())->toBe(0);
    });

    it('only offers the known icons', function () {
        Livewire::test(CreateContactItem::class)
            ->fillForm(['label' => 'Nope', 'icon' => 'fa-solid fa-unknown'])
            ->call('create')
            ->assertHasFormErrors(['icon']);
    });
});

describe('edit', function () {
    beforeEach(fn () => actingAsAdmin());

    it('prefills the form', function () {
        $item = ContactItem::factory()->create([
            'icon' => 'fa-brands fa-linkedin',
            'label' => 'in/alex',
            'url' => 'https://linkedin.com/in/alex',
            'is_active' => false,
        ]);

        Livewire::test(EditContactItem::class, ['record' => $item->getKey()])
            ->assertFormSet([
                'icon' => 'fa-brands fa-linkedin',
                'label' => 'in/alex',
                'url' => 'https://linkedin.com/in/alex',
                'is_active' => false,
            ]);
    });

    it('saves changes', function () {
        $item = ContactItem::factory()->create(['label' => 'old']);

        Livewire::test(EditContactItem::class, ['record' => $item->getKey()])
            ->fillForm(['label' => 'new label', 'url' => null, 'is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $item->refresh();

        expect($item->label)->toBe('new label')
            ->and($item->url)->toBeNull()
            ->and($item->is_active)->toBeFalse();
    });

    it('rejects an empty label or a malformed url', function () {
        $item = ContactItem::factory()->create(['label' => 'keep']);

        Livewire::test(EditContactItem::class, ['record' => $item->getKey()])
            ->fillForm(['label' => '', 'url' => 'nonsense'])
            ->call('save')
            ->assertHasFormErrors(['label' => 'required', 'url' => 'url']);

        expect($item->fresh()->label)->toBe('keep');
    });

    it('deletes the record from the header action', function () {
        $item = ContactItem::factory()->create();

        Livewire::test(EditContactItem::class, ['record' => $item->getKey()])
            ->callAction(DeleteAction::class)
            ->assertNotified()
            ->assertRedirect();

        expect(ContactItem::query()->count())->toBe(0);
    });
});
