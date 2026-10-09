<?php

use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TrustProxies;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies as LaravelTrustProxies;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The site sits behind Cloudflare and nginx on this machine. Who is believed about the visitor is
        // App\Http\Middleware\TrustProxies, the same class as in the other avstelematics apps. Never '*':
        // a visitor could then pick their own IP, which would also defeat the rate limits.
        $middleware->replace(LaravelTrustProxies::class, TrustProxies::class);

        // Only the visitor's address and scheme are taken from the proxy. Host and port come from the
        // request itself: a forwarded host would let a visitor choose the site's own host in its links.
        $middleware->trustProxies(
            headers: Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_PROTO
        );

        $middleware->append(SecurityHeaders::class);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Cloudflare's ranges (monicahq/laravel-cloudflare). Every deploy reloads them too, so this
        // only matters between deploys. Does nothing when LARAVEL_CLOUDFLARE_ENABLED=false.
        $schedule->command('cloudflare:reload')->weeklyOn(1, '06:17')->withoutOverlapping(10);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
