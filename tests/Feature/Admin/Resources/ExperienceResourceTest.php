<?php

use App\Filament\Resources\Experiences\ExperienceResource;
use App\Filament\Resources\Experiences\Pages\CreateExperience;
use App\Filament\Resources\Experiences\Pages\EditExperience;
use App\Filament\Resources\Experiences\Pages\ListExperiences;
use App\Models\Experience;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

/**
 * The bullet repeater holds one `value` row per bullet.
 *
 * @param  list<string>  $values
 * @return list<array{value: string}>
 */
function experienceBulletRows(array $values): array
{
    return array_map(fn (string $value): array => ['value' => $value], $values);
}

describe('access', function () {
    it('redirects guests to the admin login', function () {
        $experience = Experience::factory()->create();

        $this->get(ExperienceResource::getUrl('index'))->assertRedirect('/admin/login');
        $this->get(ExperienceResource::getUrl('create'))->assertRedirect('/admin/login');
        $this->get(ExperienceResource::getUrl('edit', ['record' => $experience]))->assertRedirect('/admin/login');
    });

    it('lets an admin open the pages', function () {
        actingAsAdmin();
        $experience = Experience::factory()->create();

        $this->get(ExperienceResource::getUrl('index'))->assertOk();
        $this->get(ExperienceResource::getUrl('create'))->assertOk();
        $this->get(ExperienceResource::getUrl('edit', ['record' => $experience]))->assertOk();
    });
});

describe('list', function () {
    beforeEach(fn () => actingAsAdmin());

    it('shows the records, active or not', function () {
        $active = Experience::factory()->create();
        $inactive = Experience::factory()->inactive()->create();

        Livewire::test(ListExperiences::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$active, $inactive])
            ->assertCountTableRecords(2);
    });

    it('shows the formatted period and flags the current role', function () {
        $past = Experience::factory()->create(['start_date' => '2018-01-15', 'end_date' => '2020-02-20']);
        $current = Experience::factory()->current()->create(['start_date' => '2021-03-01']);

        Livewire::test(ListExperiences::class)
            ->assertTableColumnStateSet('period', 'Jan 2018 – Feb 2020', $past)
            ->assertTableColumnStateSet('period', 'Mar 2021 – Present', $current)
            ->assertTableColumnStateSet('is_current', false, $past)
            ->assertTableColumnStateSet('is_current', true, $current);
    });

    it('orders by sort order by default', function () {
        $second = Experience::factory()->create(['sort_order' => 2]);
        $first = Experience::factory()->create(['sort_order' => 1]);
        $third = Experience::factory()->create(['sort_order' => 3]);

        Livewire::test(ListExperiences::class)
            ->assertCanSeeTableRecords([$first, $second, $third], inOrder: true);
    });

    it('sorts by title and company', function () {
        $a = Experience::factory()->create(['title' => 'Architect', 'company' => 'Zeta']);
        $b = Experience::factory()->create(['title' => 'Backend Dev', 'company' => 'Acme']);

        Livewire::test(ListExperiences::class)
            ->sortTable('title')
            ->assertCanSeeTableRecords([$a, $b], inOrder: true)
            ->sortTable('title', 'desc')
            ->assertCanSeeTableRecords([$b, $a], inOrder: true)
            ->sortTable('company')
            ->assertCanSeeTableRecords([$b, $a], inOrder: true);
    });

    it('searches by title and company', function () {
        $dev = Experience::factory()->create(['title' => 'Senior Developer', 'company' => 'Acme Inc']);
        $lead = Experience::factory()->create(['title' => 'Team Lead', 'company' => 'Globex']);

        Livewire::test(ListExperiences::class)
            ->searchTable('Developer')
            ->assertCanSeeTableRecords([$dev])
            ->assertCanNotSeeTableRecords([$lead])
            ->searchTable('Globex')
            ->assertCanSeeTableRecords([$lead])
            ->assertCanNotSeeTableRecords([$dev]);
    });

    it('toggles the active state from the table', function () {
        $experience = Experience::factory()->create();

        Livewire::test(ListExperiences::class)
            ->call('updateTableColumnState', 'is_active', (string) $experience->getKey(), false);

        expect($experience->fresh()->is_active)->toBeFalse();
    });

    it('can be reordered by sort order', function () {
        $a = Experience::factory()->create(['sort_order' => 1]);
        $b = Experience::factory()->create(['sort_order' => 2]);
        $c = Experience::factory()->create(['sort_order' => 3]);

        Livewire::test(ListExperiences::class)->call('reorderTable', [$c->getKey(), $b->getKey(), $a->getKey()]);

        expect(Experience::query()->ordered()->pluck('id')->all())->toBe([$c->id, $b->id, $a->id]);
    });

    it('deletes records in bulk', function () {
        $experiences = Experience::factory()->count(3)->create();

        Livewire::test(ListExperiences::class)
            ->selectTableRecords($experiences->take(2)->pluck('id')->all())
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertNotified();

        expect(Experience::query()->count())->toBe(1);
    });
});

describe('create', function () {
    beforeEach(fn () => actingAsAdmin());

    it('persists a valid past role with bullet points', function () {
        Livewire::test(CreateExperience::class)
            ->fillForm([
                'title' => 'Backend Developer',
                'company' => 'Acme Inc',
                'start_date' => '2018-01-01',
                'end_date' => '2020-06-30',
                'bullets' => experienceBulletRows(['Built APIs', 'Mentored juniors']),
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $experience = Experience::query()->firstOrFail();

        expect($experience->title)->toBe('Backend Developer')
            ->and($experience->company)->toBe('Acme Inc')
            ->and($experience->start_date->toDateString())->toBe('2018-01-01')
            ->and($experience->end_date?->toDateString())->toBe('2020-06-30')
            ->and($experience->bullets)->toBe(['Built APIs', 'Mentored juniors'])
            ->and($experience->is_current)->toBeFalse()
            ->and($experience->is_active)->toBeTrue();
    });

    it('persists a current role without an end date', function () {
        Livewire::test(CreateExperience::class)
            ->fillForm([
                'title' => 'Lead',
                'company' => 'Globex',
                'start_date' => '2022-01-01',
                'is_current' => true,
                'bullets' => [],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $experience = Experience::query()->firstOrFail();

        expect($experience->is_current)->toBeTrue()
            ->and($experience->end_date)->toBeNull()
            ->and($experience->bullets)->toBe([]);
    });

    it('hides the end date for a current role', function () {
        Livewire::test(CreateExperience::class)
            ->assertFormFieldVisible('end_date')
            ->fillForm(['is_current' => true])
            ->assertFormFieldHidden('end_date')
            ->fillForm(['is_current' => false])
            ->assertFormFieldVisible('end_date');
    });

    it('requires a title, company and start date', function () {
        Livewire::test(CreateExperience::class)
            ->fillForm(['title' => null, 'company' => null, 'start_date' => null])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required', 'company' => 'required', 'start_date' => 'required']);

        expect(Experience::query()->count())->toBe(0);
    });

    it('starts with one blank bullet row that has to be filled in or removed', function () {
        Livewire::test(CreateExperience::class)
            ->fillForm(['title' => 'Dev', 'company' => 'Acme', 'start_date' => '2020-01-01'])
            ->call('create')
            ->assertHasFormErrors();

        expect(Experience::query()->count())->toBe(0);
    });

    it('rejects an empty bullet point', function () {
        Livewire::test(CreateExperience::class)
            ->fillForm([
                'title' => 'Dev',
                'company' => 'Acme',
                'start_date' => '2020-01-01',
                'bullets' => experienceBulletRows(['Fine bullet', '']),
            ])
            ->call('create')
            ->assertHasFormErrors();

        expect(Experience::query()->count())->toBe(0);
    });
});

describe('edit', function () {
    beforeEach(fn () => actingAsAdmin());

    it('prefills the form', function () {
        $experience = Experience::factory()->create([
            'title' => 'Staff Engineer',
            'company' => 'Initech',
            'start_date' => '2019-05-01',
            'end_date' => '2021-04-01',
            'bullets' => ['Led migration', 'Cut costs'],
            'is_active' => false,
        ]);

        $page = Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->assertFormSet([
                'title' => 'Staff Engineer',
                'company' => 'Initech',
                'start_date' => '2019-05-01',
                'end_date' => '2021-04-01',
                'is_current' => false,
                'is_active' => false,
            ])
            ->assertFormFieldVisible('end_date');

        expect(array_column($page->get('data.bullets'), 'value'))->toBe(['Led migration', 'Cut costs']);
    });

    it('hides the end date for an existing current role', function () {
        $experience = Experience::factory()->current()->create();

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->assertFormSet(['is_current' => true])
            ->assertFormFieldHidden('end_date');
    });

    it('saves changes, including the bullet points', function () {
        $experience = Experience::factory()->create(['title' => 'Old', 'bullets' => ['one']]);

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->fillForm(['title' => 'New', 'bullets' => experienceBulletRows(['first', 'second', 'third'])])
            ->call('save')
            ->assertHasNoFormErrors();

        $experience->refresh();

        expect($experience->title)->toBe('New')
            ->and($experience->bullets)->toBe(['first', 'second', 'third']);
    });

    it('rejects an empty company', function () {
        $experience = Experience::factory()->create(['company' => 'Keep me']);

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->fillForm(['company' => ''])
            ->call('save')
            ->assertHasFormErrors(['company' => 'required']);

        expect($experience->fresh()->company)->toBe('Keep me');
    });

    it('deletes the record from the header action', function () {
        $experience = Experience::factory()->create();

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->callAction(DeleteAction::class)
            ->assertNotified()
            ->assertRedirect();

        expect(Experience::query()->count())->toBe(0);
    });
});
