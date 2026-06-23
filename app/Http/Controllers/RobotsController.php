<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $url = rtrim(config('app.url'), '/');

        $content = implode("\n", [
            'User-agent: *',
            'Disallow: /admin',
            '',
            "Sitemap: {$url}/sitemap.xml",
        ]);

        return response($content, 200, ['Content-Type' => 'text/plain']);
    }
}
