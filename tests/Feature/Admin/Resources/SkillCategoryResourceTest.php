<?php

use App\Filament\Resources\SkillCategories\Pages\CreateSkillCategory;
use App\Filament\Resources\SkillCategories\Pages\EditSkillCategory;
use App\Filament\Resources\SkillCategories\Pages\ListSkillCategories;
use App\Filament\Resources\SkillCategories\SkillCategoryResource;
use App\Models\SkillCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

/**
 * The skills repeater holds one `value` row per skill.
 *
 * @param  list<string>  $values
 * @return list<array{value: string}>
 */
function skillCategoryRows(array $values): array
{
    return array_map(fn (string $value): array => ['value' => $value], $values);
}

describe('access', function () {
    it('redirects guests to the admin login', function () {
        $category = SkillCategory::factory()->create();

        $this->get(SkillCategoryResource::getUrl('index'))->assertRedirect('/admin/login');
        $this->get(SkillCategoryResource::getUrl('create'))->assertRedirect('/admin/login');
        $this->get(SkillCategoryResource::getUrl('edit', ['record' => $category]))->assertRedirect('/admin/login');
    });

    it('lets an admin open the pages', function () {
        actingAsAdmin();
        $category = SkillCategory::factory()->create();

        $this->get(SkillCategoryResource::getUrl('index'))->assertOk();
        $this->get(SkillCategoryResource::getUrl('create'))->assertOk();
        $this->get(SkillCategoryResource::getUrl('edit', ['record' => $category]))->assertOk();
    });
});

describe('list', function () {
    beforeEach(fn () => actingAsAdmin());

    it('shows the records, active or not', function () {
        $active = SkillCategory::factory()->create();
        $inactive = SkillCategory::factory()->inactive()->create();

        Livewire::test(ListSkillCategories::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$active, $inactive])
            ->assertCountTableRecords(2);
    });

    it('shows how many skills a category holds', function () {
        $three = SkillCategory::factory()->create(['items' => ['PHP', 'JS', 'SQL']]);
        $none = SkillCategory::factory()->create(['items' => null]);

        Livewire::test(ListSkillCategories::class)
            ->assertTableColumnStateSet('items', '3 skills', $three)
            ->assertTableColumnStateSet('items', '0 skills', $none);
    });

    it('orders by sort order by default', function () {
        $second = SkillCategory::factory()->create(['sort_order' => 2]);
        $first = SkillCategory::factory()->create(['sort_order' => 1]);

        Livewire::test(ListSkillCategories::class)
            ->assertCanSeeTableRecords([$first, $second], inOrder: true);
    });

    it('sorts by name', function () {
        $a = SkillCategory::factory()->create(['name' => 'Backend']);
        $b = SkillCategory::factory()->create(['name' => 'Tools']);

        Livewire::test(ListSkillCategories::class)
            ->sortTable('name')
            ->assertCanSeeTableRecords([$a, $b], inOrder: true)
            ->sortTable('name', 'desc')
            ->assertCanSeeTableRecords([$b, $a], inOrder: true);
    });

    it('searches by name', function () {
        $backend = SkillCategory::factory()->create(['name' => 'Backend']);
        $tools = SkillCategory::factory()->create(['name' => 'Tools']);

        Livewire::test(ListSkillCategories::class)
            ->searchTable('Tool')
            ->assertCanSeeTableRecords([$tools])
            ->assertCanNotSeeTableRecords([$backend]);
    });

    it('toggles the active state from the table', function () {
        $category = SkillCategory::factory()->create();

        Livewire::test(ListSkillCategories::class)
            ->call('updateTableColumnState', 'is_active', (string) $category->getKey(), false);

        expect($category->fresh()->is_active)->toBeFalse();
    });

    it('can be reordered by sort order', function () {
        $a = SkillCategory::factory()->create(['sort_order' => 1]);
        $b = SkillCategory::factory()->create(['sort_order' => 2]);

        Livewire::test(ListSkillCategories::class)->call('reorderTable', [$b->getKey(), $a->getKey()]);

        expect(SkillCategory::query()->ordered()->pluck('id')->all())->toBe([$b->id, $a->id]);
    });

    it('deletes records in bulk', function () {
        $categories = SkillCategory::factory()->count(3)->create();

        Livewire::test(ListSkillCategories::class)
            ->selectTableRecords($categories->take(2)->pluck('id')->all())
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertNotified();

        expect(SkillCategory::query()->count())->toBe(1);
    });
});

describe('create', function () {
    beforeEach(fn () => actingAsAdmin());

    it('persists a valid category with its skills in order', function () {
        Livewire::test(CreateSkillCategory::class)
            ->fillForm([
                'name' => 'Languages',
                'items' => skillCategoryRows(['PHP', 'TypeScript', 'SQL']),
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $category = SkillCategory::query()->firstOrFail();

        expect($category->name)->toBe('Languages')
            ->and($category->items)->toBe(['PHP', 'TypeScript', 'SQL'])
            ->and($category->is_active)->toBeTrue();
    });

    it('requires a name', function () {
        Livewire::test(CreateSkillCategory::class)
            ->fillForm(['name' => null, 'items' => []])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);

        expect(SkillCategory::query()->count())->toBe(0);
    });

    it('rejects an empty skill', function () {
        Livewire::test(CreateSkillCategory::class)
            ->fillForm(['name' => 'Tools', 'items' => skillCategoryRows(['Git', ''])])
            ->call('create')
            ->assertHasFormErrors();

        expect(SkillCategory::query()->count())->toBe(0);
    });
});

describe('edit', function () {
    beforeEach(fn () => actingAsAdmin());

    it('prefills the form', function () {
        $category = SkillCategory::factory()->create(['name' => 'Databases', 'items' => ['MySQL', 'Redis'], 'is_active' => false]);

        $page = Livewire::test(EditSkillCategory::class, ['record' => $category->getKey()])
            ->assertFormSet(['name' => 'Databases', 'is_active' => false]);

        expect(array_column($page->get('data.items'), 'value'))->toBe(['MySQL', 'Redis']);
    });

    it('saves changes, including the skills', function () {
        $category = SkillCategory::factory()->create(['name' => 'Old', 'items' => ['One']]);

        Livewire::test(EditSkillCategory::class, ['record' => $category->getKey()])
            ->fillForm(['name' => 'New', 'items' => skillCategoryRows(['A', 'B']), 'is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $category->refresh();

        expect($category->name)->toBe('New')
            ->and($category->items)->toBe(['A', 'B'])
            ->and($category->is_active)->toBeFalse();
    });

    it('rejects an empty name', function () {
        $category = SkillCategory::factory()->create(['name' => 'Keep me']);

        Livewire::test(EditSkillCategory::class, ['record' => $category->getKey()])
            ->fillForm(['name' => ''])
            ->call('save')
            ->assertHasFormErrors(['name' => 'required']);

        expect($category->fresh()->name)->toBe('Keep me');
    });

    it('deletes the record from the header action', function () {
        $category = SkillCategory::factory()->create();

        Livewire::test(EditSkillCategory::class, ['record' => $category->getKey()])
            ->callAction(DeleteAction::class)
            ->assertNotified()
            ->assertRedirect();

        expect(SkillCategory::query()->count())->toBe(0);
    });
});
