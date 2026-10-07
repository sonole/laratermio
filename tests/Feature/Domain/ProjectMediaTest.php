<?php

use App\Models\Project;
use App\Support\Media\ProjectMediaPathGenerator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileUnacceptableForCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;

const DOMAIN_STUB_IMAGE = 'stubs/laratermio/projects/todo-app/main.webp';

beforeEach(function () {
    $this->domainDiskRoot = storage_path('framework/testing/disks/domain-project-media');

    File::deleteDirectory($this->domainDiskRoot);
    File::ensureDirectoryExists($this->domainDiskRoot.'-source');
    config(['filesystems.disks.public.root' => $this->domainDiskRoot]);
    Storage::forgetDisk('public');
});

afterEach(function () {
    File::deleteDirectory($this->domainDiskRoot);
    File::deleteDirectory($this->domainDiskRoot.'-source');
    Storage::forgetDisk('public');
});

/** A throw-away copy of the stub image, so the original stub is never moved or touched. */
function domainImageCopy(string $name): string
{
    $path = storage_path('framework/testing/disks/domain-project-media-source/'.$name);
    File::copy(public_path(DOMAIN_STUB_IMAGE), $path);

    return $path;
}

/** The smallest byte sequence that is detected as an MP4 video. */
function domainVideoFile(string $name): string
{
    $path = storage_path('framework/testing/disks/domain-project-media-source/'.$name);
    File::put($path, "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom\x00\x00\x00\x08free");

    return $path;
}

/** A media record that was never saved, enough for the path generator. */
function domainMedia(int $modelId = 7, int $id = 42): Media
{
    $media = new Media;
    $media->model_id = $modelId;
    $media->id = $id;

    return $media;
}

describe('media collections', function () {
    it('registers the main image, gallery and video collections on the public disk', function () {
        $collections = Project::factory()->make()->getRegisteredMediaCollections()->keyBy('name');

        expect($collections->keys()->all())->toBe(['main_image', 'gallery', 'video_file'])
            ->and($collections->pluck('diskName')->unique()->all())->toBe(['public']);
    });

    it('keeps a single file for the main image and the video, but many for the gallery', function () {
        $collections = Project::factory()->make()->getRegisteredMediaCollections()->keyBy('name');

        expect($collections['main_image']->singleFile)->toBeTrue()
            ->and($collections['video_file']->singleFile)->toBeTrue()
            ->and($collections['gallery']->singleFile)->toBeFalse();
    });

    it('only accepts images and videos in the matching collections', function () {
        $collections = Project::factory()->make()->getRegisteredMediaCollections()->keyBy('name');
        $accepts = fn (string $name, string $mime) => ($collections[$name]->acceptsMimeTypes === [] || in_array($mime, $collections[$name]->acceptsMimeTypes, true));

        expect($accepts('main_image', 'image/webp'))->toBeTrue()
            ->and($accepts('main_image', 'video/mp4'))->toBeFalse()
            ->and($accepts('gallery', 'image/png'))->toBeTrue()
            ->and($accepts('gallery', 'application/pdf'))->toBeFalse()
            ->and($accepts('video_file', 'video/mp4'))->toBeTrue()
            ->and($accepts('video_file', 'video/webm'))->toBeTrue()
            ->and($accepts('video_file', 'image/png'))->toBeFalse();
    });
});

describe('empty state', function () {
    it('has no image, gallery or video until media is attached', function () {
        $project = Project::factory()->create();

        expect($project->imageUrl())->toBe('')
            ->and($project->galleryUrls())->toBe([])
            ->and($project->videoFileUrl())->toBeNull();
    });
});

describe('with attached media', function () {
    it('returns the URL of the main image', function () {
        $project = Project::factory()->create();
        $media = $project->addMedia(domainImageCopy('cover.webp'))->toMediaCollection('main_image');

        expect($project->imageUrl())->toEndWith("/storage/uploads/projects/{$project->id}/{$media->id}/cover.webp");
        Storage::disk('public')->assertExists("uploads/projects/{$project->id}/{$media->id}/cover.webp");
    });

    it('replaces the main image when a new one is added', function () {
        $project = Project::factory()->create();
        $project->addMedia(domainImageCopy('first.webp'))->toMediaCollection('main_image');
        $project->addMedia(domainImageCopy('second.webp'))->toMediaCollection('main_image');

        expect($project->fresh()->getMedia('main_image'))->toHaveCount(1)
            ->and($project->fresh()->imageUrl())->toEndWith('/second.webp');
    });

    it('lists every gallery image in upload order', function () {
        $project = Project::factory()->create();
        $project->addMedia(domainImageCopy('one.webp'))->toMediaCollection('gallery');
        $project->addMedia(domainImageCopy('two.webp'))->toMediaCollection('gallery');

        $urls = $project->fresh()->galleryUrls();

        expect($urls)->toHaveCount(2)
            ->and($urls[0])->toEndWith('/one.webp')
            ->and($urls[1])->toEndWith('/two.webp');
    });

    it('returns the URL of the uploaded video', function () {
        $project = Project::factory()->create();
        $media = $project->addMedia(domainVideoFile('demo.mp4'))->toMediaCollection('video_file');

        expect($project->videoFileUrl())->toEndWith("/storage/uploads/projects/{$project->id}/{$media->id}/demo.mp4");
    });

    it('rejects a file the collection does not accept', function () {
        $project = Project::factory()->create();

        $project->addMedia(domainVideoFile('demo.mp4'))->toMediaCollection('main_image');
    })->throws(FileUnacceptableForCollection::class);

    it('keeps media of different projects apart', function () {
        $a = Project::factory()->create();
        $b = Project::factory()->create();
        $a->addMedia(domainImageCopy('a.webp'))->toMediaCollection('main_image');

        expect($a->imageUrl())->not->toBe('')
            ->and($b->imageUrl())->toBe('');
    });

    it('removes the media and its files when the project is deleted', function () {
        $project = Project::factory()->create();
        $media = $project->addMedia(domainImageCopy('cover.webp'))->toMediaCollection('main_image');
        $path = "uploads/projects/{$project->id}/{$media->id}/cover.webp";

        Storage::disk('public')->assertExists($path);

        $project->delete();

        expect(Media::query()->count())->toBe(0);
        Storage::disk('public')->assertMissing($path);
    });
});

describe('ProjectMediaPathGenerator', function () {
    it('stores media under the project and media ids', function () {
        expect((new ProjectMediaPathGenerator)->getPath(domainMedia()))->toBe('uploads/projects/7/42/');
    });

    it('nests conversions and responsive images below the media directory', function () {
        $generator = new ProjectMediaPathGenerator;

        expect($generator->getPathForConversions(domainMedia()))->toBe('uploads/projects/7/42/conversions/')
            ->and($generator->getPathForResponsiveImages(domainMedia()))->toBe('uploads/projects/7/42/responsive-images/');
    });

    it('is the generator the media library uses for projects', function () {
        $project = Project::factory()->create();
        $media = $project->addMedia(domainImageCopy('cover.webp'))->toMediaCollection('main_image');

        expect(PathGeneratorFactory::create($media))->toBeInstanceOf(ProjectMediaPathGenerator::class);
    });
});
