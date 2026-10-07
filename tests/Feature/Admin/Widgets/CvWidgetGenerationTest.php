<?php

use App\Filament\Widgets\CvWidget;
use App\Services\CvService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->disk = storage_path('framework/testing/disks/admin-pages');
    File::deleteDirectory($this->disk);
    config(['filesystems.disks.public.root' => $this->disk]);
    Storage::forgetDisk('public');

    actingAsAdmin();
    seedEnglishCv();
});

afterEach(function () {
    File::deleteDirectory($this->disk);
    Storage::forgetDisk('public');
});

describe('CV widget before any PDF exists', function () {
    it('starts empty and offers to generate the CV', function () {
        Livewire::test(CvWidget::class)
            ->assertSet('cvExists', false)
            ->assertSet('lastGeneratedAt', null)
            ->assertSee('CV Generator')
            ->assertSeeHtml('>Generate CV</span>')
            ->assertDontSeeHtml('>Regenerate CV</span>')
            ->assertSee('No compiled CV PDF has been generated yet.')
            ->assertDontSee('View Generated PDF');
    });

    it('does not list other languages while none are switched on', function () {
        $widget = Livewire::test(CvWidget::class)->assertDontSee('Download in another language');

        expect($widget->instance()->extraLanguages())->toBe([]);
    });

    it('names the main language', function () {
        expect(Livewire::test(CvWidget::class)->instance()->baseLanguage())->toBe('English');
    });
});

describe('generating the CV', function () {
    it('stores the PDF and switches the widget to regenerate mode', function () {
        $widget = Livewire::test(CvWidget::class)
            ->call('generate')
            ->assertSet('cvExists', true)
            ->assertSeeHtml('>Regenerate CV</span>')
            ->assertDontSeeHtml('>Generate CV</span>')
            ->assertSee('View Generated PDF')
            ->assertSee('Last compiled PDF file:')
            ->assertDontSee('No compiled CV PDF has been generated yet.')
            ->assertNotified('CV generated successfully');

        expect(Storage::disk('public')->exists(CvService::PATH))->toBeTrue()
            ->and(Storage::disk('public')->get(CvService::PATH))->toStartWith('%PDF')
            ->and($widget->get('lastGeneratedAt'))->not->toBeNull();
    });

    it('links to the public CV once it exists', function () {
        Livewire::test(CvWidget::class)
            ->call('generate')
            ->assertSee(route('cv'));

        expect(Livewire::test(CvWidget::class)->instance()->cvUrl())->toBe(url('/cv'));
    });

    it('replaces the stored PDF when regenerated', function () {
        Storage::disk('public')->put(CvService::PATH, 'stale');

        Livewire::test(CvWidget::class)->call('generate');

        expect(Storage::disk('public')->get(CvService::PATH))->toStartWith('%PDF');
    });
});

describe('CV widget with an existing PDF', function () {
    it('offers to regenerate and says when the file was last compiled', function () {
        Storage::disk('public')->put(CvService::PATH, '%PDF-1.4 old');
        touch(Storage::disk('public')->path(CvService::PATH), now()->subDays(2)->getTimestamp());

        Livewire::test(CvWidget::class)
            ->assertSet('cvExists', true)
            ->assertSet('lastGeneratedAt', '2 days ago')
            ->assertSeeHtml('>Regenerate CV</span>')
            ->assertSee('Last compiled PDF file:')
            ->assertSee('2 days ago')
            ->assertSee('View Generated PDF')
            ->assertSee('Data Synchronization Note');
    });

    it('links to the template preview page from the note', function () {
        Storage::disk('public')->put(CvService::PATH, '%PDF-1.4 old');

        Livewire::test(CvWidget::class)->assertSee(route('filament.admin.pages.template-preview'));
    });
});
