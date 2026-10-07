<?php

use App\Services\CvService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/** @return array<int, array<string, string>> */
function webSitemapUrls(string $xml): array
{
    $document = simplexml_load_string($xml);
    $document->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');

    $urls = [];

    foreach ($document->xpath('//s:url') as $url) {
        $urls[] = array_map('strval', (array) $url);
    }

    return $urls;
}

beforeEach(function () {
    $this->webSeoDisk = storage_path('framework/testing/disks/web-seo');
    config(['filesystems.disks.public.root' => $this->webSeoDisk, 'app.url' => 'https://portfolio.test']);
    Storage::forgetDisk('public');
});

afterEach(function () {
    File::deleteDirectory($this->webSeoDisk);
});

describe('robots.txt', function () {
    it('is served as plain text', function () {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    });

    it('allows crawling, blocks the admin panel and points at the sitemap', function () {
        expect($this->get('/robots.txt')->getContent())->toBe(implode("\n", [
            'User-agent: *',
            'Disallow: /admin',
            '',
            'Sitemap: https://portfolio.test/sitemap.xml',
        ]));
    });

    it('does not double the slash when the app url has a trailing one', function () {
        config(['app.url' => 'https://portfolio.test/']);

        $this->get('/robots.txt')->assertSee('Sitemap: https://portfolio.test/sitemap.xml', false);
    });
});

describe('sitemap.xml', function () {
    it('is served as XML', function () {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    });

    it('is a valid sitemap that lists only the home page when there is no CV', function () {
        $xml = $this->get('/sitemap.xml')->getContent();

        expect($xml)->toStartWith('<?xml version="1.0" encoding="UTF-8"?>')
            ->and(webSitemapUrls($xml))->toBe([
                ['loc' => 'https://portfolio.test', 'changefreq' => 'monthly', 'priority' => '1.0'],
            ]);
    });

    it('adds the CV, with the date it was generated, once one exists', function () {
        Storage::disk('public')->put(CvService::PATH, 'pdf');
        touch(Storage::disk('public')->path(CvService::PATH), strtotime('2024-05-06 12:00:00 UTC'));

        $urls = webSitemapUrls($this->get('/sitemap.xml')->getContent());

        expect($urls)->toHaveCount(2)
            ->and($urls[0]['loc'])->toBe('https://portfolio.test')
            ->and($urls[1])->toBe([
                'loc' => 'https://portfolio.test/cv',
                'lastmod' => '2024-05-06',
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ]);
    });

    it('builds urls from the app url without a trailing slash', function () {
        config(['app.url' => 'https://portfolio.test/']);

        expect(webSitemapUrls($this->get('/sitemap.xml')->getContent())[0]['loc'])->toBe('https://portfolio.test');
    });
});

describe('docs page', function () {
    it('renders the docs view', function () {
        $this->get('/docs')
            ->assertOk()
            ->assertViewIs('docs')
            ->assertSee('<title>laratermio— docs</title>', false);
    });

    it('is reachable by name', function () {
        expect(route('docs', absolute: false))->toBe('/docs');
    });
});
