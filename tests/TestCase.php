<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // `public/build` is git-ignored; tests must not depend on a frontend build.
        $this->withoutVite();

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
