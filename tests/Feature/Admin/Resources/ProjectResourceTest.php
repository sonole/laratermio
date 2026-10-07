<?php

use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The simple repeaters (bullets, tech) hold one `value` row per entry.
 *
 * @param  list<string>  $values
 * @return list<array{value: string}>
 */
function projectRows(array $values): array
{
    return array_map(fn (string $value): array => ['value' => $value], $values);
}

/** A tiny file the media library recognises as an mp4 video (it sniffs the content, not the name). */
function projectVideo(string $name): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom");
}

beforeEach(function () {
    config(['filesystems.disks.public.root' => storage_path('framework/testing/disks/admin-resources')]);
    Storage::forgetDisk('public');
});

afterEach(function () {
    File::deleteDirectory(storage_path('framework/testing/disks/admin-resources'));
});

describe('access', function () {
    it('redirects guests to the admin login', function () {
        $project = Project::factory()->create();

        $this->get(ProjectResource::getUrl('index'))->assertRedirect('/admin/login');
        $this->get(ProjectResource::getUrl('create'))->assertRedirect('/admin/login');
        $this->get(ProjectResource::getUrl('edit', ['record' => $project]))->assertRedirect('/admin/login');
    });

    it('lets an admin open the pages', function () {
        actingAsAdmin();
        $project = Project::factory()->create();

        $this->get(ProjectResource::getUrl('index'))->assertOk();
        $this->get(ProjectResource::getUrl('create'))->assertOk();
        $this->get(ProjectResource::getUrl('edit', ['record' => $project]))->assertOk();
    });
});

describe('list', function () {
    beforeEach(fn () => actingAsAdmin());

    it('shows the records, active or not', function () {
        $active = Project::factory()->create();
        $inactive = Project::factory()->inactive()->create();

        Livewire::test(ListProjects::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$active, $inactive])
            ->assertCountTableRecords(2);
    });

    it('shows the columns', function () {
        Project::factory()->create();

        Livewire::test(ListProjects::class)
            ->assertTableColumnExists('main_image')
            ->assertTableColumnExists('name')
            ->assertTableColumnExists('subtitle')
            ->assertTableColumnExists('is_active');
    });

    it('orders by sort order by default', function () {
        $second = Project::factory()->create(['sort_order' => 2]);
        $first = Project::factory()->create(['sort_order' => 1]);
        $third = Project::factory()->create(['sort_order' => 3]);

        Livewire::test(ListProjects::class)
            ->assertCanSeeTableRecords([$first, $second, $third], inOrder: true);
    });

    it('sorts by name', function () {
        $a = Project::factory()->create(['name' => 'Alpha']);
        $b = Project::factory()->create(['name' => 'Bravo']);

        Livewire::test(ListProjects::class)
            ->sortTable('name')
            ->assertCanSeeTableRecords([$a, $b], inOrder: true)
            ->sortTable('name', 'desc')
            ->assertCanSeeTableRecords([$b, $a], inOrder: true);
    });

    it('searches by name', function () {
        $laratermio = Project::factory()->create(['name' => 'Laratermio']);
        $other = Project::factory()->create(['name' => 'Something else']);

        Livewire::test(ListProjects::class)
            ->searchTable('termio')
            ->assertCanSeeTableRecords([$laratermio])
            ->assertCanNotSeeTableRecords([$other]);
    });

    it('toggles the active state from the table', function () {
        $project = Project::factory()->create();

        Livewire::test(ListProjects::class)
            ->call('updateTableColumnState', 'is_active', (string) $project->getKey(), false);

        expect($project->fresh()->is_active)->toBeFalse();
    });

    it('can be reordered by sort order', function () {
        $a = Project::factory()->create(['sort_order' => 1]);
        $b = Project::factory()->create(['sort_order' => 2]);
        $c = Project::factory()->create(['sort_order' => 3]);

        Livewire::test(ListProjects::class)->call('reorderTable', [$c->getKey(), $a->getKey(), $b->getKey()]);

        expect(Project::query()->ordered()->pluck('id')->all())->toBe([$c->id, $a->id, $b->id]);
    });

    it('deletes records in bulk', function () {
        $projects = Project::factory()->count(3)->create();

        Livewire::test(ListProjects::class)
            ->selectTableRecords($projects->take(2)->pluck('id')->all())
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertNotified();

        expect(Project::query()->count())->toBe(1);
    });
});

describe('create', function () {
    beforeEach(fn () => actingAsAdmin());

    it('persists a valid project with its bullets, links and technologies', function () {
        Livewire::test(CreateProject::class)
            ->fillForm([
                'name' => 'Laratermio',
                'subtitle' => 'Terminal portfolio',
                'video_url' => 'https://www.youtube.com/watch?v=abc123',
                'bullets' => projectRows(['Browser based', 'Admin panel']),
                'links' => [
                    ['label' => 'GitHub', 'url' => 'https://github.com/alex/laratermio'],
                    ['label' => 'Demo', 'url' => 'https://example.com'],
                ],
                'tech' => projectRows(['Laravel', 'Livewire', 'Tailwind']),
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $project = Project::query()->firstOrFail();

        expect($project->name)->toBe('Laratermio')
            ->and($project->subtitle)->toBe('Terminal portfolio')
            ->and($project->video_url)->toBe('https://www.youtube.com/watch?v=abc123')
            ->and($project->bullets)->toBe(['Browser based', 'Admin panel'])
            ->and($project->links)->toBe([
                ['label' => 'GitHub', 'url' => 'https://github.com/alex/laratermio'],
                ['label' => 'Demo', 'url' => 'https://example.com'],
            ])
            ->and($project->tech)->toBe(['Laravel', 'Livewire', 'Tailwind'])
            ->and($project->is_active)->toBeTrue();
    });

    it('only needs a name', function () {
        Livewire::test(CreateProject::class)
            ->fillForm(['name' => 'Minimal', 'bullets' => [], 'links' => [], 'tech' => []])
            ->call('create')
            ->assertHasNoFormErrors();

        $project = Project::query()->firstOrFail();

        expect($project->name)->toBe('Minimal')
            ->and($project->subtitle)->toBeNull()
            ->and($project->video_url)->toBeNull();
    });

    it('requires a name', function () {
        Livewire::test(CreateProject::class)
            ->fillForm(['name' => null, 'bullets' => [], 'links' => [], 'tech' => []])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);

        expect(Project::query()->count())->toBe(0);
    });

    it('requires the video url to be a valid url', function () {
        Livewire::test(CreateProject::class)
            ->fillForm(['name' => 'Video', 'video_url' => 'not a url', 'bullets' => [], 'links' => [], 'tech' => []])
            ->call('create')
            ->assertHasFormErrors(['video_url' => 'url']);
    });

    it('requires a label and a valid url on every link', function () {
        Livewire::test(CreateProject::class)
            ->fillForm([
                'name' => 'Links',
                'bullets' => [],
                'tech' => [],
                'links' => [
                    ['label' => '', 'url' => 'https://example.com'],
                    ['label' => 'Broken', 'url' => 'nope'],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors();

        expect(Project::query()->count())->toBe(0);
    });

    it('rejects an empty bullet or technology', function () {
        Livewire::test(CreateProject::class)
            ->fillForm(['name' => 'Rows', 'bullets' => projectRows(['']), 'links' => [], 'tech' => projectRows(['PHP'])])
            ->call('create')
            ->assertHasFormErrors();

        Livewire::test(CreateProject::class)
            ->fillForm(['name' => 'Rows', 'bullets' => [], 'links' => [], 'tech' => projectRows([''])])
            ->call('create')
            ->assertHasFormErrors();

        expect(Project::query()->count())->toBe(0);
    });

    it('attaches the main image, gallery and video to the project', function () {
        Livewire::test(CreateProject::class)
            ->fillForm([
                'name' => 'With media',
                'bullets' => [],
                'links' => [],
                'tech' => [],
                'main_image' => UploadedFile::fake()->image('cover.jpg', 640, 480),
                'gallery' => [
                    UploadedFile::fake()->image('one.png', 320, 240),
                    UploadedFile::fake()->image('two.png', 320, 240),
                ],
                'video_file' => projectVideo('demo.mp4'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $project = Project::query()->firstOrFail();

        expect($project->getMedia('main_image'))->toHaveCount(1)
            ->and($project->getFirstMedia('main_image')->name)->toBe('cover')
            ->and($project->getMedia('gallery'))->toHaveCount(2)
            ->and($project->getMedia('video_file'))->toHaveCount(1)
            ->and($project->getFirstMedia('video_file')->mime_type)->toBe('video/mp4')
            ->and($project->videoFileUrl())->toContain('.mp4')
            ->and($project->imageUrl())->toContain('/uploads/projects/'.$project->id.'/')
            ->and($project->galleryUrls())->toHaveCount(2);

        foreach (Media::query()->get() as $media) {
            expect($media->disk)->toBe('public')
                ->and($media->getPath())->toStartWith(storage_path('framework/testing/disks/admin-resources/uploads/projects/'.$project->id.'/'));

            Storage::disk('public')->assertExists('uploads/projects/'.$project->id.'/'.$media->id.'/'.$media->file_name);
        }
    });

    it('rejects a main image that is not an image', function () {
        Livewire::test(CreateProject::class)
            ->fillForm([
                'name' => 'Bad image',
                'bullets' => [],
                'links' => [],
                'tech' => [],
                'main_image' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasFormErrors(['main_image']);

        expect(Media::query()->count())->toBe(0);
    });

    it('rejects a video file that is not a video', function () {
        Livewire::test(CreateProject::class)
            ->fillForm([
                'name' => 'Bad video',
                'bullets' => [],
                'links' => [],
                'tech' => [],
                'video_file' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasFormErrors(['video_file']);

        expect(Media::query()->count())->toBe(0);
    });
});

describe('edit', function () {
    beforeEach(fn () => actingAsAdmin());

    it('prefills the form', function () {
        $project = Project::factory()->create([
            'name' => 'Portfolio',
            'subtitle' => 'My site',
            'video_url' => 'https://vimeo.com/123',
            'bullets' => ['Fast', 'Small'],
            'links' => [['label' => 'Source', 'url' => 'https://example.com/src']],
            'tech' => ['PHP', 'MySQL'],
            'is_active' => false,
        ]);

        $page = Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->assertFormSet([
                'name' => 'Portfolio',
                'subtitle' => 'My site',
                'video_url' => 'https://vimeo.com/123',
                'is_active' => false,
            ]);

        expect(array_column($page->get('data.bullets'), 'value'))->toBe(['Fast', 'Small'])
            ->and(array_column($page->get('data.tech'), 'value'))->toBe(['PHP', 'MySQL'])
            ->and(array_values($page->get('data.links')))->toBe([['label' => 'Source', 'url' => 'https://example.com/src']]);
    });

    it('saves changes to the text fields and repeaters', function () {
        $project = Project::factory()->create(['name' => 'Old', 'bullets' => ['one'], 'tech' => ['PHP'], 'links' => []]);

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm([
                'name' => 'New',
                'subtitle' => 'Fresh subtitle',
                'bullets' => projectRows(['first', 'second']),
                'tech' => projectRows(['Go']),
                'links' => [['label' => 'Docs', 'url' => 'https://docs.example.com']],
                'is_active' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $project->refresh();

        expect($project->name)->toBe('New')
            ->and($project->subtitle)->toBe('Fresh subtitle')
            ->and($project->bullets)->toBe(['first', 'second'])
            ->and($project->tech)->toBe(['Go'])
            ->and($project->links)->toBe([['label' => 'Docs', 'url' => 'https://docs.example.com']])
            ->and($project->is_active)->toBeFalse();
    });

    it('rejects an empty name', function () {
        $project = Project::factory()->create(['name' => 'Keep me']);

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm(['name' => ''])
            ->call('save')
            ->assertHasFormErrors(['name' => 'required']);

        expect($project->fresh()->name)->toBe('Keep me');
    });

    it('shows the existing main image and replaces it with a new upload', function () {
        $project = Project::factory()->create();
        $old = $project->addMedia(UploadedFile::fake()->image('old.jpg', 300, 200))->toMediaCollection('main_image');

        $page = Livewire::test(EditProject::class, ['record' => $project->getKey()]);

        expect($page->get('data.main_image'))->toBe([$old->uuid => $old->uuid]);

        $page->fillForm(['main_image' => UploadedFile::fake()->image('new.jpg', 300, 200)])
            ->call('save')
            ->assertHasNoFormErrors();

        $media = $project->refresh()->getMedia('main_image');

        expect($media)->toHaveCount(1)
            ->and($media->first()->id)->not->toBe($old->id)
            ->and($media->first()->name)->toBe('new')
            ->and(Media::query()->whereKey($old->id)->exists())->toBeFalse();
    });

    it('removes the main image when the upload is cleared', function () {
        $project = Project::factory()->create();
        $project->addMedia(UploadedFile::fake()->image('old.jpg', 300, 200))->toMediaCollection('main_image');

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm(['main_image' => []])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($project->refresh()->getMedia('main_image'))->toHaveCount(0);
    });

    it('adds gallery images next to the existing ones', function () {
        $project = Project::factory()->create();
        $project->addMedia(UploadedFile::fake()->image('first.png', 200, 200))->toMediaCollection('gallery');

        // Kept files are listed by uuid, new ones are added next to them.
        $kept = $project->getMedia('gallery')->mapWithKeys(fn (Media $media): array => [$media->uuid => $media->uuid])->all();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm(['gallery' => $kept + ['new' => UploadedFile::fake()->image('second.png', 200, 200)]])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($project->refresh()->getMedia('gallery')->pluck('name')->sort()->values()->all())->toBe(['first', 'second']);
    });

    it('deletes the record and its media from the header action', function () {
        $project = Project::factory()->create();
        $media = $project->addMedia(UploadedFile::fake()->image('cover.jpg', 300, 200))->toMediaCollection('main_image');
        $path = 'uploads/projects/'.$project->id.'/'.$media->id.'/'.$media->file_name;

        Storage::disk('public')->assertExists($path);

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->callAction(DeleteAction::class)
            ->assertNotified()
            ->assertRedirect();

        expect(Project::query()->count())->toBe(0)
            ->and(Media::query()->count())->toBe(0);

        Storage::disk('public')->assertMissing($path);
    });
});
