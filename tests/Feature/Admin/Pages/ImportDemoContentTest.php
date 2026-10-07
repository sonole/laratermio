<?php

use App\Enums\SettingKey;
use App\Filament\Pages\ImportDemoContent;
use App\Models\ContactItem;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Project;
use App\Models\Setting;
use App\Models\SkillCategory;
use App\Services\CvService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

const ADMIN_IMPORT_SECTIONS = ['settings', 'educations', 'experiences', 'projects', 'skills', 'contact', 'cv'];

beforeEach(function () {
    // The seeders write to the public disk: keep them away from the real one and from other tests.
    $this->disk = storage_path('framework/testing/disks/admin-pages');
    File::deleteDirectory($this->disk);
    config(['filesystems.disks.public.root' => $this->disk]);
    Storage::forgetDisk('public');

    actingAsAdmin();
    seedSystem();
});

afterEach(function () {
    File::deleteDirectory($this->disk);
    Storage::forgetDisk('public');
});

/** Put one recognisable row in every content table, plus a name, an old upload and a stale CV. */
function adminImportSentinels(): void
{
    Education::factory()->create(['title' => 'Sentinel education']);
    Experience::factory()->create(['title' => 'Sentinel experience']);
    SkillCategory::factory()->create(['name' => 'Sentinel skills']);
    ContactItem::factory()->create(['label' => 'sentinel@example.com']);
    Project::factory()->create(['name' => 'Sentinel project']);

    cvSetting(SettingKey::Name, 'Sentinel Name');
    Storage::disk('public')->put('uploads/settings/old-upload.txt', 'old');
    Storage::disk('public')->put(CvService::PATH, 'stale cv');
}

/** Run the import with only `$sections` switched on. */
function adminRunImport(string ...$sections): void
{
    $toggles = collect(ADMIN_IMPORT_SECTIONS)->mapWithKeys(fn (string $section) => [$section => in_array($section, $sections, true)])->all();

    Livewire::test(ImportDemoContent::class)
        ->fillForm($toggles)
        ->callAction('import')
        ->assertNotified('Demo content imported');
}

/** @return array<string, bool> sentinel still present, per section */
function adminImportSurvivors(): array
{
    return [
        'educations' => Education::query()->where('title', 'Sentinel education')->exists(),
        'experiences' => Experience::query()->where('title', 'Sentinel experience')->exists(),
        'skills' => SkillCategory::query()->where('name', 'Sentinel skills')->exists(),
        'contact' => ContactItem::query()->where('label', 'sentinel@example.com')->exists(),
        'projects' => Project::query()->where('name', 'Sentinel project')->exists(),
        'settings' => Setting::query()->where('key', 'name')->value('value') === 'Sentinel Name',
        'cv' => Storage::disk('public')->get(CvService::PATH) === 'stale cv',
    ];
}

describe('import page', function () {
    it('renders for a signed-in admin', function () {
        $this->get('/admin/import-demo-content')
            ->assertOk()
            ->assertSee('Sections to import');
    });

    it('switches every section on by default', function () {
        Livewire::test(ImportDemoContent::class)
            ->assertFormSet(array_fill_keys(ADMIN_IMPORT_SECTIONS, true))
            ->assertActionExists('import');
    });

    it('asks for confirmation before importing', function () {
        Livewire::test(ImportDemoContent::class)
            ->mountAction('import')
            ->assertActionMounted('import');

        expect(Education::query()->count())->toBe(0);
    });

    it('leaves everything alone when no section is switched on', function () {
        adminImportSentinels();

        adminRunImport();

        expect(adminImportSurvivors())->each->toBeTrue();
    });
});

describe('importing one section', function () {
    it('replaces the educations only', function () {
        adminImportSentinels();

        adminRunImport('educations');

        expect(Education::query()->count())->toBe(3)
            ->and(Education::query()->where('title', 'Sentinel education')->exists())->toBeFalse()
            ->and(Education::query()->where('is_certification', true)->count())->toBe(2)
            ->and(adminImportSurvivors())->toMatchArray(['educations' => false, 'experiences' => true, 'skills' => true, 'contact' => true, 'projects' => true, 'settings' => true, 'cv' => true]);
    });

    it('replaces the experiences only', function () {
        adminImportSentinels();

        adminRunImport('experiences');

        expect(Experience::query()->count())->toBe(3)
            ->and(Experience::query()->where('is_current', true)->count())->toBe(1)
            ->and(Experience::query()->where('title', 'Senior Bug Creator')->value('bullets'))->toHaveCount(4)
            ->and(adminImportSurvivors())->toMatchArray(['educations' => true, 'experiences' => false, 'skills' => true, 'contact' => true, 'projects' => true, 'settings' => true, 'cv' => true]);
    });

    it('replaces the skill categories only', function () {
        adminImportSentinels();

        adminRunImport('skills');

        expect(SkillCategory::query()->count())->toBe(4)
            ->and(SkillCategory::query()->orderBy('sort_order')->first()->name)->toBe('Definitely Know')
            ->and(adminImportSurvivors())->toMatchArray(['educations' => true, 'experiences' => true, 'skills' => false, 'contact' => true, 'projects' => true, 'settings' => true, 'cv' => true]);
    });

    it('replaces the contact items only', function () {
        adminImportSentinels();

        adminRunImport('contact');

        expect(ContactItem::query()->count())->toBe(4)
            ->and(ContactItem::query()->pluck('label'))->toContain('dev@example.com')
            ->and(adminImportSurvivors())->toMatchArray(['educations' => true, 'experiences' => true, 'skills' => true, 'contact' => false, 'projects' => true, 'settings' => true, 'cv' => true]);
    });

    it('replaces the projects and their media only', function () {
        adminImportSentinels();
        Project::query()->where('name', 'Sentinel project')->first()
            ->addMedia(public_path('stubs/laratermio/projects/todo-app/main.webp'))
            ->preservingOriginal()
            ->toMediaCollection('main_image');
        $oldMediaId = Media::query()->value('id');

        adminRunImport('projects');

        $projects = Project::query()->orderBy('sort_order')->get();
        $image = $projects->first()->getFirstMedia('main_image');

        expect($projects)->toHaveCount(3)
            ->and($projects->pluck('tech')->first())->toContain('Laravel')
            ->and($image)->not->toBeNull()
            ->and(Storage::disk('public')->exists($image->getPathRelativeToRoot()))->toBeTrue()
            ->and(Media::query()->whereKey($oldMediaId)->exists())->toBeFalse()
            ->and(Media::query()->count())->toBe(3)
            ->and(adminImportSurvivors())->toMatchArray(['educations' => true, 'experiences' => true, 'skills' => true, 'contact' => true, 'projects' => false, 'settings' => true, 'cv' => true]);
    });

    it('resets the settings and wipes old uploads, but not the content', function () {
        adminImportSentinels();

        adminRunImport('settings');

        $setting = fn (SettingKey $key) => Setting::query()->where('key', $key->value)->value('value');

        expect($setting(SettingKey::Name))->toBe('Dev McDevface')
            ->and($setting(SettingKey::Role))->toBe('Professional Coffee-to-Code Converter')
            ->and($setting(SettingKey::AsciiArtEnabled))->toBe('1')
            ->and($setting(SettingKey::AsciiArtColor))->toBe('#4ade80')
            ->and($setting(SettingKey::AsciiArt))->toBe('uploads/settings/ascii-art.txt')
            ->and($setting(SettingKey::SeoOgImage))->toBe('uploads/settings/og-image.png')
            ->and(Storage::disk('public')->exists('uploads/settings/ascii-art.txt'))->toBeTrue()
            ->and(Storage::disk('public')->exists('uploads/settings/og-image.png'))->toBeTrue()
            ->and(Storage::disk('public')->exists('uploads/settings/old-upload.txt'))->toBeFalse()
            ->and(adminImportSurvivors())->toMatchArray(['educations' => true, 'experiences' => true, 'skills' => true, 'contact' => true, 'projects' => true, 'settings' => false, 'cv' => true]);
    });

    it('does not touch settings it does not know about', function () {
        cvSetting(SettingKey::CvTitleSkills, 'My toolbox');

        adminRunImport('settings');

        expect(Setting::query()->where('key', SettingKey::CvTitleSkills->value)->value('value'))->toBe('My toolbox');
    });

    it('regenerates the CV PDF only', function () {
        adminImportSentinels();

        adminRunImport('cv');

        expect(Storage::disk('public')->get(CvService::PATH))->toStartWith('%PDF')
            ->and(adminImportSurvivors())->toMatchArray(['educations' => true, 'experiences' => true, 'skills' => true, 'contact' => true, 'projects' => true, 'settings' => true, 'cv' => false]);
    });
});

describe('importing everything', function () {
    it('replaces every section in one go', function () {
        adminImportSentinels();

        adminRunImport(...ADMIN_IMPORT_SECTIONS);

        expect(adminImportSurvivors())->each->toBeFalse()
            ->and(Education::query()->count())->toBe(3)
            ->and(Experience::query()->count())->toBe(3)
            ->and(Project::query()->count())->toBe(3)
            ->and(SkillCategory::query()->count())->toBe(4)
            ->and(ContactItem::query()->count())->toBe(4)
            ->and(Setting::query()->where('key', 'name')->value('value'))->toBe('Dev McDevface')
            ->and(Storage::disk('public')->get(CvService::PATH))->toStartWith('%PDF');
    });

    it('can be repeated without piling up duplicates', function () {
        adminRunImport(...ADMIN_IMPORT_SECTIONS);
        adminRunImport(...ADMIN_IMPORT_SECTIONS);

        expect(Education::query()->count())->toBe(3)
            ->and(Experience::query()->count())->toBe(3)
            ->and(Project::query()->count())->toBe(3)
            ->and(Media::query()->count())->toBe(3);
    });
});
