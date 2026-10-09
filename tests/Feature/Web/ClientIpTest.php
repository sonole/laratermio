<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

/*
 * `$request->ip()` has to be the visitor, and a visitor must not be able to pick their own address
 * with an `X-Forwarded-For` header. The only proxies believed are Cloudflare's (monicahq/laravel-cloudflare,
 * from the cache), loopback and the server's own address (App\Http\Middleware\TrustProxies), and
 * whatever the app names itself through Laravel's `trustProxies(at: ...)`.
 */

/** The address of a proxy in front of the app, other than Cloudflare. */
const WEB_HOP = '100.64.0.7';
const WEB_CLOUDFLARE_EDGE = '103.21.244.5';

/** The server's own public address (documentation range): what PHP sees as the peer on Plesk. */
const WEB_OWN_ADDRESS = '192.0.2.10';

beforeEach(function () {
    Route::get('/_client-ip', fn (Request $request) => $request->ip());
    Route::get('/_scheme', fn (Request $request) => $request->getScheme());
    Route::get('/_host', fn (Request $request) => $request->getHost().':'.$request->getPort());
});

/** Switch Cloudflare on, with its published list faked and shortened to the ranges these tests use. */
function webEnableCloudflare(): void
{
    config(['laravelcloudflare.enabled' => true]);
    Cache::forget((string) config('laravelcloudflare.cache'));

    Http::fake([
        'www.cloudflare.com/ips-v4' => Http::response("103.21.244.0/22\n173.245.48.0/20\n"),
        'www.cloudflare.com/ips-v6' => Http::response("2606:4700::/32\n"),
    ]);
}

function webClientIp(array $headers, string $from = WEB_HOP, array $server = []): string
{
    return test()
        ->withServerVariables(['REMOTE_ADDR' => $from, ...$server])
        ->withHeaders($headers)
        ->get('/_client-ip')
        ->assertOk()
        ->getContent();
}

describe('with Cloudflare switched off and no other proxy in front (LARAVEL_CLOUDFLARE_ENABLED=false)', function () {
    beforeEach(fn () => config(['laravelcloudflare.enabled' => false]));

    it('is the connecting address, whatever the visitor writes in X-Forwarded-For', function () {
        expect(webClientIp(['X-Forwarded-For' => '6.6.6.6'], from: '198.51.100.7'))->toBe('198.51.100.7');
    });

    it('ignores CF-Connecting-IP, which only Cloudflare is entitled to write', function () {
        expect(webClientIp(['CF-Connecting-IP' => '1.1.1.1'], from: '198.51.100.7'))->toBe('198.51.100.7');
    });

    it('does not believe a forwarded protocol', function () {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/_scheme')
            ->assertSee('http');
    });

    it('does not fetch anything from Cloudflare', function () {
        Http::fake();

        webClientIp([], from: '198.51.100.7');

        Http::assertNothingSent();
    });
});

describe('Cloudflare straight to the server (the default)', function () {
    beforeEach(fn () => webEnableCloudflare());

    it('is the visitor Cloudflare reports', function () {
        expect(webClientIp(['X-Forwarded-For' => '203.0.113.9'], from: WEB_CLOUDFLARE_EDGE))->toBe('203.0.113.9');
    });

    it('understands IPv6 visitors and IPv6 Cloudflare edges', function () {
        expect(webClientIp(['X-Forwarded-For' => '2001:db8::7'], from: '2606:4700::1234'))->toBe('2001:db8::7');
    });

    it('does not believe anyone who is not Cloudflare', function () {
        expect(webClientIp(['X-Forwarded-For' => '6.6.6.6'], from: '198.51.100.7'))->toBe('198.51.100.7');
    });

    it('does not let a visitor claim to be coming through Cloudflare', function () {
        $ip = webClientIp(['X-Forwarded-For' => WEB_CLOUDFLARE_EDGE, 'CF-Connecting-IP' => '1.1.1.1'], from: '198.51.100.7');

        expect($ip)->toBe('198.51.100.7');
    });

    it('cannot be fooled by a forged X-Forwarded-For in front of the real one', function () {
        // Cloudflare appends the address it saw, so what the visitor wrote stays on the left.
        expect(webClientIp(['X-Forwarded-For' => '6.6.6.6, 203.0.113.9'], from: WEB_CLOUDFLARE_EDGE))->toBe('203.0.113.9');
    });

    it('does not take CF-Connecting-IP over the forwarded chain', function () {
        // `LARAVEL_CLOUDFLARE_REPLACE_IP` would; it stays off because it trusts the header from anyone.
        $ip = webClientIp(['X-Forwarded-For' => '203.0.113.9', 'CF-Connecting-IP' => '1.1.1.1'], from: WEB_CLOUDFLARE_EDGE);

        expect($ip)->toBe('203.0.113.9');
    });

    it('reads the scheme Cloudflare forwards', function () {
        $this->withServerVariables(['REMOTE_ADDR' => WEB_CLOUDFLARE_EDGE])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/_scheme')
            ->assertSee('https');
    });

    it('asks Cloudflare for its ranges once, not on every request', function () {
        webClientIp([], from: WEB_CLOUDFLARE_EDGE);
        webClientIp([], from: WEB_CLOUDFLARE_EDGE);

        Http::assertSentCount(2); // one request for the IPv4 list and one for the IPv6 list
    });

    it('refreshes the ranges every Monday morning', function () {
        // The schedule is registered when the console starts, so start it before looking.
        $this->artisan('schedule:list')->expectsOutputToContain('cloudflare:reload')->assertSuccessful();

        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command, 'cloudflare:reload'));

        expect($event->expression)->toBe('17 6 * * 1');
    });
});

describe('Cloudflare, then a proxy named with trustProxies(at: ...)', function () {
    beforeEach(function () {
        webEnableCloudflare();
        TrustProxies::at(['REMOTE_ADDR']); // what the README tells you to put in bootstrap/app.php
    });

    it('is the visitor when the proxy appended the Cloudflare edge', function () {
        $ip = webClientIp(['X-Forwarded-For' => '6.6.6.6, 203.0.113.9, '.WEB_CLOUDFLARE_EDGE]);

        expect($ip)->toBe('203.0.113.9');
    });

    it('still believes the proxy next to Cloudflare', function () {
        $this->withServerVariables(['REMOTE_ADDR' => WEB_HOP])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/_scheme')
            ->assertSee('https');
    });
});

describe('nginx on the same machine (Plesk)', function () {
    beforeEach(fn () => webEnableCloudflare());

    it('is the visitor when PHP sees the server itself as the peer', function () {
        $ip = webClientIp(
            ['X-Forwarded-For' => '6.6.6.6, 203.0.113.9, '.WEB_CLOUDFLARE_EDGE],
            from: WEB_OWN_ADDRESS,
            server: ['SERVER_ADDR' => WEB_OWN_ADDRESS],
        );

        expect($ip)->toBe('203.0.113.9');
    });

    it('is the visitor when PHP sees loopback as the peer', function () {
        expect(webClientIp(['X-Forwarded-For' => '6.6.6.6, 203.0.113.9, '.WEB_CLOUDFLARE_EDGE], from: '127.0.0.1'))->toBe('203.0.113.9');
    });

    it('does not believe a forwarded host or port, even from a proxy it trusts', function () {
        $peers = [
            'nginx on loopback' => ['127.0.0.1', []],
            'a Cloudflare edge' => [WEB_CLOUDFLARE_EDGE, []],
            'the server itself' => [WEB_OWN_ADDRESS, ['SERVER_ADDR' => WEB_OWN_ADDRESS]],
        ];

        foreach ($peers as $label => [$from, $server]) {
            $seen = $this->withServerVariables(['REMOTE_ADDR' => $from, ...$server])
                ->withHeaders(['X-Forwarded-Host' => 'evil.example', 'X-Forwarded-Port' => '8080'])
                ->get('/_host')
                ->assertOk()
                ->getContent();

            // The request's own host and port (the test client's), whatever the forwarded headers say.
            expect($seen)->toBe('localhost:80', $label);
        }
    });

    it('believes the scheme the server itself forwards', function () {
        $this->withServerVariables(['REMOTE_ADDR' => WEB_OWN_ADDRESS, 'SERVER_ADDR' => WEB_OWN_ADDRESS])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/_scheme')
            ->assertSee('https');
    });

    it('believes nobody else, even when the same headers arrive', function () {
        $ip = webClientIp(
            ['X-Forwarded-For' => '6.6.6.6, 203.0.113.9'],
            from: '198.51.100.4',
            server: ['SERVER_ADDR' => WEB_OWN_ADDRESS],
        );

        expect($ip)->toBe('198.51.100.4');
    });

    it('does not carry over to a later request from another address', function () {
        webClientIp(['X-Forwarded-For' => '203.0.113.9, '.WEB_CLOUDFLARE_EDGE], from: WEB_OWN_ADDRESS, server: ['SERVER_ADDR' => WEB_OWN_ADDRESS]);

        $ip = webClientIp(['X-Forwarded-For' => '6.6.6.6'], from: WEB_OWN_ADDRESS, server: ['SERVER_ADDR' => '192.0.2.99']);

        expect($ip)->toBe(WEB_OWN_ADDRESS);
    });

    it('is still believed when Cloudflare is switched off', function () {
        config(['laravelcloudflare.enabled' => false]);

        $ip = webClientIp(
            ['X-Forwarded-For' => '203.0.113.9'],
            from: WEB_OWN_ADDRESS,
            server: ['SERVER_ADDR' => WEB_OWN_ADDRESS],
        );

        expect($ip)->toBe('203.0.113.9');
    });
});
