<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Monicahq\Cloudflare\Http\Middleware\TrustProxies as TrustCloudflareProxies;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Cloudflare's ranges are trusted when LARAVEL_CLOUDFLARE_ENABLED is on, and nothing else is:
        // with no proxy in front, a visitor could choose their own IP. Another proxy goes in `at: [...]`.
        $middleware->replace(TrustProxies::class, TrustCloudflareProxies::class);

        $middleware->trustProxies(
            headers: Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO
        );

        $middleware->append(SecurityHeaders::class);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Keeps Cloudflare's IP ranges current when LARAVEL_CLOUDFLARE_ENABLED is on; does nothing otherwise.
        $schedule->command('cloudflare:reload')->daily();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
