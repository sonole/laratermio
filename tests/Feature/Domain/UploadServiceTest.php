<?php

use App\Facades\Upload;
use App\Services\UploadService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->domainDiskRoot = storage_path('framework/testing/disks/domain-upload');

    File::deleteDirectory($this->domainDiskRoot);
    config(['filesystems.disks.public.root' => $this->domainDiskRoot]);
    Storage::forgetDisk('public');
});

afterEach(function () {
    File::deleteDirectory($this->domainDiskRoot);
    Storage::forgetDisk('public');
});

describe('Upload facade', function () {
    it('resolves to the shared UploadService singleton', function () {
        expect(Upload::getFacadeRoot())->toBeInstanceOf(UploadService::class)
            ->and(Upload::getFacadeRoot())->toBe(app(UploadService::class));
    });
});

describe('resolveUrl()', function () {
    it('prefixes a relative storage path with /storage/', function () {
        expect(Upload::resolveUrl('uploads/settings/favicon.png'))->toBe('/storage/uploads/settings/favicon.png');
    });

    it('returns an absolute path unchanged', function () {
        expect(Upload::resolveUrl('/favicon.ico'))->toBe('/favicon.ico')
            ->and(Upload::resolveUrl('/storage/uploads/a.png'))->toBe('/storage/uploads/a.png');
    });

    it('returns an empty string for an empty path', function () {
        expect(Upload::resolveUrl(''))->toBe('');
    });
});

describe('copyStubToStorage()', function () {
    it('copies a stub into a storage directory and returns the relative path', function () {
        $path = Upload::copyStubToStorage('laratermio/settings/ascii-art.txt', 'uploads/settings');

        expect($path)->toBe('uploads/settings/ascii-art.txt');
        Storage::disk('public')->assertExists($path);
        expect(Storage::disk('public')->get($path))->toBe(file_get_contents(public_path('stubs/laratermio/settings/ascii-art.txt')));
    });

    it('writes into the public disk root', function () {
        Upload::copyStubToStorage('laratermio/settings/og-image.png', 'uploads/settings');

        expect(file_exists($this->domainDiskRoot.'/uploads/settings/og-image.png'))->toBeTrue();
    });

    it('accepts a stub path with a leading slash', function () {
        $path = Upload::copyStubToStorage('/laratermio/settings/og-image.png', 'uploads/nav-items');

        expect($path)->toBe('uploads/nav-items/og-image.png');
        Storage::disk('public')->assertExists($path);
    });

    it('returns null and stores nothing when the stub does not exist', function () {
        expect(Upload::copyStubToStorage('laratermio/settings/missing.txt', 'uploads/settings'))->toBeNull();

        Storage::disk('public')->assertMissing('uploads/settings/missing.txt');
        expect(Storage::disk('public')->allFiles())->toBeEmpty();
    });

    it('overwrites the file when copied again', function () {
        Upload::copyStubToStorage('laratermio/settings/ascii-art.txt', 'uploads/settings');
        Storage::disk('public')->put('uploads/settings/ascii-art.txt', 'edited');

        Upload::copyStubToStorage('laratermio/settings/ascii-art.txt', 'uploads/settings');

        expect(Storage::disk('public')->get('uploads/settings/ascii-art.txt'))->not->toBe('edited')
            ->and(Storage::disk('public')->allFiles())->toHaveCount(1);
    });

    it('stores a copied file that can then be deleted and resolved to a URL', function () {
        $path = Upload::copyStubToStorage('laratermio/settings/ascii-art.txt', 'uploads/settings');

        expect(Upload::resolveUrl($path))->toBe('/storage/uploads/settings/ascii-art.txt');

        Storage::disk('public')->delete($path);

        Storage::disk('public')->assertMissing($path);
    });
});
