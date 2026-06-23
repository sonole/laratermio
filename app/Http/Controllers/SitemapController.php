<?php

namespace App\Http\Controllers;

use App\Services\CvService;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __construct(private CvService $cvService) {}

    public function __invoke(): Response
    {
        $base = rtrim(config('app.url'), '/');

        $urls = [
            [
                'loc' => $base,
                'changefreq' => 'monthly',
                'priority' => '1.0',
            ],
        ];

        if ($this->cvService->exists()) {
            $lastmod = $this->cvService->lastGeneratedAt()?->toDateString();
            $urls[] = [
                'loc' => "{$base}/cv",
                'lastmod' => $lastmod,
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= "    <url>\n";
            foreach ($url as $tag => $value) {
                if ($value !== null) {
                    $xml .= "        <{$tag}>{$value}</{$tag}>\n";
                }
            }
            $xml .= "    </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
