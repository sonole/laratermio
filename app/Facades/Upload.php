<?php

namespace App\Facades;

use App\Services\UploadService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string resolveUrl(string $path)
 * @method static string|null copyStubToStorage(string $stubRelativePath, string $directory)
 */
class Upload extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return UploadService::class;
    }
}
