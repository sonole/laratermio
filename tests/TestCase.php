<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    /**
     * A few real Cloudflare ranges, enough for the addresses the tests use.
     *
     * @var array<int, string>
     */
    protected const array CLOUDFLARE_RANGES = ['173.245.48.0/20', '103.21.244.0/22', '2606:4700::/32'];

    protected function setUp(): void
    {
        parent::setUp();

        // `public/build` is git-ignored; tests must not depend on a frontend build.
        $this->withoutVite();

        // As after a deploy (`cloudflare:reload`): Cloudflare's ranges are in the cache, so a request
        // never fetches them. Nothing may reach Cloudflare unless a test fakes it.
        Cache::forever((string) config('laravelcloudflare.cache'), self::CLOUDFLARE_RANGES);
        Http::preventStrayRequests();

        // DomPDF writes its font cache next to the fonts; keep test runs from touching the
        // font cache that lives in `storage/fonts`.
        $fontCache = storage_path('framework/testing/fonts');
        File::ensureDirectoryExists($fontCache);
        config([
            'dompdf.options.font_dir' => $fontCache,
            'dompdf.options.font_cache' => $fontCache,
        ]);
    }
}
