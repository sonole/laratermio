<?php

use App\Filament\Resources\Educations\EducationResource;
use App\Filament\Resources\Educations\Pages\CreateEducation;
use App\Filament\Resources\Educations\Pages\EditEducation;
use App\Filament\Resources\Educations\Pages\ListEducations;
use App\Models\Education;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

describe('access', function () {
    it('redirects guests to the admin login', function () {
        $education = Education::factory()->create();

        $this->get(EducationResource::getUrl('index'))->assertRedirect('/admin/login');
        $this->get(EducationResource::getUrl('create'))->assertRedirect('/admin/login');
        $this->get(EducationResource::getUrl('edit', ['record' => $education]))->assertRedirect('/admin/login');
    });

    it('lets an admin open the pages', function () {
        actingAsAdmin();
        $education = Education::factory()->create();

        $this->get(EducationResource::getUrl('index'))->assertOk();
        $this->get(EducationResource::getUrl('create'))->assertOk();
        $this->get(EducationResource::getUrl('edit', ['record' => $education]))->assertOk();
    });
});

describe('list', function () {
    beforeEach(fn () => actingAsAdmin());

    it('shows the records, active or not', function () {
        $active = Education::factory()->create();
        $inactive = Education::factory()->inactive()->create();

        Livewire::test(ListEducations::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$active, $inactive])
            ->assertCountTableRecords(2);
    });

    it('shows the columns, including the formatted period', function () {
        $education = Education::factory()->create(['start_date' => '2012-09-01', 'end_date' => '2017-06-01']);

        Livewire::test(ListEducations::class)
            ->assertTableColumnExists('title')
            ->assertTableColumnExists('institution')
            ->assertTableColumnExists('is_certification')
            ->assertTableColumnExists('is_active')
            ->assertTableColumnStateSet('period', 'Sep 2012 – Jun 2017', $education);
    });

    it('flags certifications', function () {
        $degree = Education::factory()->create();
        $cert = Education::factory()->certification()->create();

        Livewire::test(ListEducations::class)
            ->assertTableColumnStateSet('is_certification', false, $degree)
            ->assertTableColumnStateSet('is_certification', true, $cert);
    });

    it('orders by sort order by default', function () {
        $second = Education::factory()->create(['sort_order' => 2]);
        $first = Education::factory()->create(['sort_order' => 1]);

        Livewire::test(ListEducations::class)
            ->assertCanSeeTableRecords([$first, $second], inOrder: true);
    });

    it('sorts by title and institution', function () {
        $a = Education::factory()->create(['title' => 'Alpha', 'institution' => 'Zeta University']);
        $b = Education::factory()->create(['title' => 'Bravo', 'institution' => 'Acme College']);

        Livewire::test(ListEducations::class)
            ->sortTable('title', 'desc')
            ->assertCanSeeTableRecords([$b, $a], inOrder: true)
            ->sortTable('institution')
            ->assertCanSeeTableRecords([$b, $a], inOrder: true);
    });

    it('searches by title and institution', function () {
        $physics = Education::factory()->create(['title' => 'BSc Physics', 'institution' => 'Athens University']);
        $aws = Education::factory()->create(['title' => 'AWS Certified', 'institution' => 'Amazon']);

        Livewire::test(ListEducations::class)
            ->searchTable('Physics')
            ->assertCanSeeTableRecords([$physics])
            ->assertCanNotSeeTableRecords([$aws])
            ->searchTable('Amazon')
            ->assertCanSeeTableRecords([$aws])
            ->assertCanNotSeeTableRecords([$physics]);
    });

    it('toggles the active state from the table', function () {
        $education = Education::factory()->create();

        Livewire::test(ListEducations::class)
            ->call('updateTableColumnState', 'is_active', (string) $education->getKey(), false);

        expect($education->fresh()->is_active)->toBeFalse();
    });

    it('can be reordered by sort order', function () {
        $a = Education::factory()->create(['sort_order' => 1]);
        $b = Education::factory()->create(['sort_order' => 2]);

        Livewire::test(ListEducations::class)->call('reorderTable', [$b->getKey(), $a->getKey()]);

        expect(Education::query()->ordered()->pluck('id')->all())->toBe([$b->id, $a->id]);
    });

    it('deletes records in bulk', function () {
        $educations = Education::factory()->count(3)->create();

        Livewire::test(ListEducations::class)
            ->selectTableRecords($educations->take(2)->pluck('id')->all())
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertNotified();

        expect(Education::query()->count())->toBe(1);
    });
});

describe('create', function () {
    beforeEach(fn () => actingAsAdmin());

    it('persists a valid degree', function () {
        Livewire::test(CreateEducation::class)
            ->fillForm([
                'title' => 'BSc Computer Science',
                'institution' => 'University of Athens',
                'start_date' => '2012-09-01',
                'end_date' => '2017-06-01',
                'description' => 'Software engineering major',
                'certificate_url' => 'https://example.com/diploma',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $education = Education::query()->firstOrFail();

        expect($education->title)->toBe('BSc Computer Science')
            ->and($education->institution)->toBe('University of Athens')
            ->and($education->start_date->toDateString())->toBe('2012-09-01')
            ->and($education->end_date?->toDateString())->toBe('2017-06-01')
            ->and($education->description)->toBe('Software engineering major')
            ->and($education->certificate_url)->toBe('https://example.com/diploma')
            ->and($education->is_certification)->toBeFalse()
            ->and($education->is_active)->toBeTrue();
    });

    it('persists a certification that has no end date', function () {
        Livewire::test(CreateEducation::class)
            ->fillForm([
                'is_certification' => true,
                'title' => 'AWS Certified Developer',
                'institution' => 'Amazon',
                'start_date' => '2022-11-01',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $education = Education::query()->firstOrFail();

        expect($education->is_certification)->toBeTrue()
            ->and($education->end_date)->toBeNull();
    });

    it('hides the end date for a certification', function () {
        Livewire::test(CreateEducation::class)
            ->assertFormFieldVisible('end_date')
            ->fillForm(['is_certification' => true])
            ->assertFormFieldHidden('end_date')
            ->fillForm(['is_certification' => false])
            ->assertFormFieldVisible('end_date');
    });

    it('requires a title, institution and start date', function () {
        Livewire::test(CreateEducation::class)
            ->fillForm(['title' => null, 'institution' => null, 'start_date' => null])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required', 'institution' => 'required', 'start_date' => 'required']);

        expect(Education::query()->count())->toBe(0);
    });

    it('requires the certificate url to be a valid url', function () {
        Livewire::test(CreateEducation::class)
            ->fillForm([
                'title' => 'Course',
                'institution' => 'Academy',
                'start_date' => '2020-01-01',
                'certificate_url' => 'not a url',
            ])
            ->call('create')
            ->assertHasFormErrors(['certificate_url' => 'url']);
    });
});

describe('edit', function () {
    beforeEach(fn () => actingAsAdmin());

    it('prefills the form', function () {
        $education = Education::factory()->create([
            'title' => 'MSc Data Science',
            'institution' => 'Tech Institute',
            'start_date' => '2018-09-01',
            'end_date' => '2020-06-01',
            'description' => 'Thesis on graphs',
            'is_active' => false,
        ]);

        Livewire::test(EditEducation::class, ['record' => $education->getKey()])
            ->assertFormSet([
                'title' => 'MSc Data Science',
                'institution' => 'Tech Institute',
                'start_date' => '2018-09-01',
                'end_date' => '2020-06-01',
                'description' => 'Thesis on graphs',
                'is_certification' => false,
                'is_active' => false,
            ])
            ->assertFormFieldVisible('end_date');
    });

    it('hides the end date for an existing certification', function () {
        $cert = Education::factory()->certification()->create();

        Livewire::test(EditEducation::class, ['record' => $cert->getKey()])
            ->assertFormSet(['is_certification' => true])
            ->assertFormFieldHidden('end_date');
    });

    it('saves changes', function () {
        $education = Education::factory()->create(['title' => 'Old title']);

        Livewire::test(EditEducation::class, ['record' => $education->getKey()])
            ->fillForm(['title' => 'New title', 'is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($education->fresh()->title)->toBe('New title')
            ->and($education->fresh()->is_active)->toBeFalse();
    });

    it('rejects an empty title', function () {
        $education = Education::factory()->create(['title' => 'Keep me']);

        Livewire::test(EditEducation::class, ['record' => $education->getKey()])
            ->fillForm(['title' => ''])
            ->call('save')
            ->assertHasFormErrors(['title' => 'required']);

        expect($education->fresh()->title)->toBe('Keep me');
    });

    it('deletes the record from the header action', function () {
        $education = Education::factory()->create();

        Livewire::test(EditEducation::class, ['record' => $education->getKey()])
            ->callAction(DeleteAction::class)
            ->assertNotified()
            ->assertRedirect();

        expect(Education::query()->count())->toBe(0);
    });
});
