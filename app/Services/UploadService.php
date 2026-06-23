<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class UploadService
{
    /**
     * Resolve a stored path (relative or absolute) to a public URL.
     * Paths already starting with '/' are returned as-is.
     * Relative paths are prefixed with '/storage/'.
     */
    public function resolveUrl(string $path): string
    {
        if (empty($path)) {
            return '';
        }

        return str_starts_with($path, '/') ? $path : '/storage/'.$path;
    }

    /**
     * Copy a stub file (relative to public/stubs/) to a storage directory
     * and return the relative storage path, or null if the stub is missing.
     */
    public function copyStubToStorage(string $stubRelativePath, string $directory): ?string
    {
        $src = public_path('stubs/'.ltrim($stubRelativePath, '/'));

        if (! file_exists($src)) {
            return null;
        }

        $contents = file_get_contents($src);

        if ($contents === false) {
            return null;
        }

        $storagePath = $directory.'/'.basename($src);
        Storage::disk('public')->put($storagePath, $contents);

        return $storagePath;
    }
}
