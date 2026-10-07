<?php

use App\Enums\SettingKey;
use App\Models\ContactItem;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Project;
use App\Models\Setting;
use App\Models\SkillCategory;
use App\Services\CvService;
use Database\Seeders\ContentSeeder;
use Database\Seeders\PersonalContentSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    $this->domainDiskRoot = storage_path('framework/testing/disks/domain-seeders');

    File::deleteDirectory($this->domainDiskRoot);
    config(['filesystems.disks.public.root' => $this->domainDiskRoot]);
    Storage::forgetDisk('public');

    // The CV is a real PDF render, which CvTest already covers.
    config(['app.seed_content.cv' => false]);
    seedSystem();
});

afterEach(function () {
    File::deleteDirectory($this->domainDiskRoot);
    Storage::forgetDisk('public');
});

describe('ContentSeeder', function () {
    it('fills every content table with the demo data', function () {
        $this->seed(ContentSeeder::class);

        expect(Experience::query()->count())->toBe(3)
            ->and(Education::query()->count())->toBe(3)
            ->and(Project::query()->count())->toBe(3)
            ->and(SkillCategory::query()->count())->toBe(4)
            ->and(ContactItem::query()->count())->toBe(4);
    });

    it('writes the demo identity into the existing settings', function () {
        $this->seed(ContentSeeder::class);

        expect(Setting::query()->where('key', SettingKey::Name->value)->value('value'))->toBe('Dev McDevface')
            ->and(Setting::query()->where('key', SettingKey::PromptHostname->value)->value('value'))->toBe('laratermio')
            ->and(Setting::query()->where('key', SettingKey::AsciiArtEnabled->value)->value('value'))->toBe('1');
    });

    it('copies the setting stubs to storage and stores their paths', function () {
        $this->seed(ContentSeeder::class);

        $ascii = Setting::query()->where('key', SettingKey::AsciiArt->value)->value('value');
        $og = Setting::query()->where('key', SettingKey::SeoOgImage->value)->value('value');

        expect($ascii)->toBe('uploads/settings/ascii-art.txt')
            ->and($og)->toBe('uploads/settings/og-image.png');
        Storage::disk('public')->assertExists([$ascii, $og]);
    });

    it('stores ordered, active records with their details', function () {
        $this->seed(ContentSeeder::class);

        $experiences = Experience::query()->ordered()->get();

        expect($experiences->pluck('sort_order')->all())->toBe([0, 1, 2])
            ->and($experiences->every(fn (Experience $experience) => $experience->is_active))->toBeTrue()
            ->and($experiences->first()->is_current)->toBeTrue()
            ->and($experiences->first()->end_date)->toBeNull()
            ->and($experiences->first()->bullets)->not->toBeEmpty()
            ->and(Education::query()->where('is_certification', true)->count())->toBe(2)
            ->and(ContactItem::query()->ordered()->first()->iconAlias())->toBe('email');
    });

    it('attaches the stub image to every project', function () {
        $this->seed(ContentSeeder::class);

        expect(Media::query()->where('collection_name', 'main_image')->count())->toBe(3);

        foreach (Project::query()->get() as $project) {
            $media = $project->getFirstMedia('main_image');

            expect($project->imageUrl())->toContain("/storage/uploads/projects/{$project->id}/")->toEndWith('/main.webp');
            Storage::disk('public')->assertExists("uploads/projects/{$project->id}/{$media->id}/main.webp");
        }
    });

    it('does not move or delete the stubs in public/stubs', function () {
        $this->seed(ContentSeeder::class);

        expect(file_exists(public_path('stubs/laratermio/projects/todo-app/main.webp')))->toBeTrue()
            ->and(file_exists(public_path('stubs/laratermio/settings/ascii-art.txt')))->toBeTrue();
    });

    it('replaces existing content instead of adding to it', function () {
        Experience::factory()->count(2)->create();
        Education::factory()->create();
        Project::factory()->create(['name' => 'Old project']);
        SkillCategory::factory()->create();
        ContactItem::factory()->create();

        $this->seed(ContentSeeder::class);

        expect(Experience::query()->count())->toBe(3)
            ->and(Education::query()->count())->toBe(3)
            ->and(Project::query()->where('name', 'Old project')->exists())->toBeFalse()
            ->and(SkillCategory::query()->count())->toBe(4)
            ->and(ContactItem::query()->count())->toBe(4);
    });

    it('gives the same result when run twice', function () {
        $this->seed(ContentSeeder::class);
        $this->seed(ContentSeeder::class);

        expect(Experience::query()->count())->toBe(3)
            ->and(Project::query()->count())->toBe(3)
            ->and(Media::query()->count())->toBe(3)
            ->and(Storage::disk('public')->allFiles('uploads/projects'))->toHaveCount(3);
    });

    it('leaves a section alone when its seed_content flag is off', function (string $flag, string $model) {
        config(["app.seed_content.$flag" => false]);
        $existing = $model::factory()->create();

        $this->seed(ContentSeeder::class);

        expect($model::query()->pluck('id')->all())->toBe([$existing->id]);
    })->with([
        ['experiences', Experience::class],
        ['educations', Education::class],
        ['projects', Project::class],
        ['skills', SkillCategory::class],
        ['contact', ContactItem::class],
    ]);

    it('leaves the settings alone when their flag is off', function () {
        config(['app.seed_content.settings' => false]);

        $this->seed(ContentSeeder::class);

        expect(Setting::query()->where('key', SettingKey::Name->value)->value('value'))->toBeNull();
        Storage::disk('public')->assertMissing('uploads/settings/ascii-art.txt');
    });

    it('generates the CV only when the flag is on', function (bool $enabled) {
        config(['app.seed_content.cv' => $enabled]);
        $this->mock(CvService::class)->shouldReceive('generate')->times($enabled ? 1 : 0);

        $this->seed(ContentSeeder::class);
    })->with([true, false]);
});

describe('PersonalContentSeeder', function () {
    it('runs without error and refreshes the content tables', function () {
        Experience::factory()->create(['title' => 'Marker experience']);
        Education::factory()->create(['title' => 'Marker education']);
        SkillCategory::factory()->create(['name' => 'Marker skills']);
        ContactItem::factory()->create(['label' => 'marker@example.test']);
        Project::factory()->create(['name' => 'Marker project']);

        $this->seed(PersonalContentSeeder::class);

        expect(Experience::query()->where('title', 'Marker experience')->exists())->toBeFalse()
            ->and(Education::query()->where('title', 'Marker education')->exists())->toBeFalse()
            ->and(SkillCategory::query()->where('name', 'Marker skills')->exists())->toBeFalse()
            ->and(ContactItem::query()->where('label', 'marker@example.test')->exists())->toBeFalse()
            ->and(Project::query()->where('name', 'Marker project')->exists())->toBeFalse();
    });

    it('does not use the demo content', function () {
        $this->seed(PersonalContentSeeder::class);

        expect(Experience::query()->where('title', 'Senior Bug Creator')->exists())->toBeFalse()
            ->and(Setting::query()->where('key', SettingKey::Name->value)->value('value'))->not->toBe('Dev McDevface');
    });

    it('is a ContentSeeder that only swaps the data', function () {
        expect(get_parent_class(PersonalContentSeeder::class))->toBe(ContentSeeder::class);
    });

    it('can run twice without leaving orphaned project media behind', function () {
        $this->seed(PersonalContentSeeder::class);
        $this->seed(PersonalContentSeeder::class);

        expect(Media::query()->whereNotIn('model_id', Project::query()->pluck('id'))->count())->toBe(0)
            ->and(Storage::disk('public')->allFiles('uploads/projects'))->toHaveCount(Media::query()->count());
    });
});
