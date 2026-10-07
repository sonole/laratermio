<?php

use App\Support\TrustedProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * `$request->ip()` has to be the visitor, and a visitor must not be able to pick their own address
 * with an `X-Forwarded-For` header. What counts as a trusted proxy is the TRUSTED_PROXIES setting.
 */

/** The address the app is actually called from: the platform's proxy, or the visitor if there is none. */
const WEB_HOP = '100.64.0.7';
const WEB_CLOUDFLARE_EDGE = '103.21.244.5';

beforeEach(function () {
    Route::get('/_client-ip', fn (Request $request) => $request->ip());
});

/** Behave as if `TRUSTED_PROXIES` were set to `$value`. */
function webTrust(?string $value): void
{
    config(['trustedproxy.proxies' => TrustedProxies::fromEnv($value)]);
}

function webClientIp(array $headers, string $from = WEB_HOP): string
{
    return test()
        ->withServerVariables(['REMOTE_ADDR' => $from])
        ->withHeaders($headers)
        ->get('/_client-ip')
        ->assertOk()
        ->getContent();
}

describe('with no proxy in front (the default)', function () {
    it('is the connecting address, whatever the visitor writes in X-Forwarded-For', function () {
        webTrust(null);

        expect(webClientIp(['X-Forwarded-For' => '6.6.6.6'], from: '198.51.100.7'))->toBe('198.51.100.7');
    });

    it('does not let a visitor claim to be coming through Cloudflare', function () {
        webTrust(null);

        $ip = webClientIp(['X-Forwarded-For' => WEB_CLOUDFLARE_EDGE, 'CF-Connecting-IP' => '1.1.1.1'], from: '198.51.100.7');

        expect($ip)->toBe('198.51.100.7');
    });

    it('does not believe a forwarded protocol', function () {
        webTrust(null);
        Route::get('/_scheme', fn (Request $request) => $request->getScheme());

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/_scheme')
            ->assertSee('http');
    });
});

describe('behind one proxy (TRUSTED_PROXIES=REMOTE_ADDR)', function () {
    beforeEach(fn () => webTrust('REMOTE_ADDR'));

    it('is the address the proxy added, not one the visitor wrote', function () {
        // The visitor sent "6.6.6.6"; the proxy then appended the address it saw them connect from.
        expect(webClientIp(['X-Forwarded-For' => '6.6.6.6, 9.9.9.9']))->toBe('9.9.9.9');
    });

    it('cannot be chosen by an X-Forwarded-For header with many entries', function () {
        expect(webClientIp(['X-Forwarded-For' => '1.1.1.1, 2.2.2.2, 3.3.3.3, 9.9.9.9']))->toBe('9.9.9.9');
    });

    it('falls back to the connecting address when there are no forwarded headers', function () {
        expect(webClientIp([]))->toBe(WEB_HOP);
    });

    it('reads the scheme and host the proxy forwards', function () {
        Route::get('/_scheme', fn (Request $request) => $request->getScheme().'://'.$request->getHost());

        $this->withServerVariables(['REMOTE_ADDR' => WEB_HOP])
            ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'apaliampelos.me'])
            ->get('/_scheme')
            ->assertSee('https://apaliampelos.me');
    });

    it('is the visitor when the request came through Cloudflare', function () {
        $ip = webClientIp([
            'X-Forwarded-For' => '6.6.6.6, '.WEB_CLOUDFLARE_EDGE,
            'CF-Connecting-IP' => '203.0.113.9',
        ]);

        expect($ip)->toBe('203.0.113.9');
    });

    it('understands IPv6 visitors and IPv6 Cloudflare edges', function () {
        $ip = webClientIp(['X-Forwarded-For' => '2606:4700::1234', 'CF-Connecting-IP' => '2001:db8::7']);

        expect($ip)->toBe('2001:db8::7');
    });

    it('ignores CF-Connecting-IP when the request did not come from Cloudflare', function () {
        // Someone reaching the origin directly can write any header; only a Cloudflare edge is believed.
        expect(webClientIp(['X-Forwarded-For' => '9.9.9.9', 'CF-Connecting-IP' => '1.1.1.1']))->toBe('9.9.9.9');
    });

    it('ignores a CF-Connecting-IP that is not an address', function () {
        $ip = webClientIp(['X-Forwarded-For' => WEB_CLOUDFLARE_EDGE, 'CF-Connecting-IP' => 'not-an-ip']);

        expect($ip)->toBe(WEB_CLOUDFLARE_EDGE);
    });
});

describe('Cloudflare straight to the server (TRUSTED_PROXIES=cloudflare)', function () {
    beforeEach(fn () => webTrust('cloudflare'));

    it('is the visitor Cloudflare reports', function () {
        $ip = webClientIp(['X-Forwarded-For' => '203.0.113.9', 'CF-Connecting-IP' => '203.0.113.9'], from: WEB_CLOUDFLARE_EDGE);

        expect($ip)->toBe('203.0.113.9');
    });

    it('does not believe anyone who is not Cloudflare', function () {
        expect(webClientIp(['X-Forwarded-For' => '6.6.6.6'], from: '198.51.100.7'))->toBe('198.51.100.7');
    });

    it('cannot be fooled by a forged X-Forwarded-For in front of the real one', function () {
        // Cloudflare appends the address it saw, so what the visitor wrote stays on the left.
        $ip = webClientIp(['X-Forwarded-For' => '6.6.6.6, 203.0.113.9'], from: WEB_CLOUDFLARE_EDGE);

        expect($ip)->toBe('203.0.113.9');
    });
});

describe('Cloudflare, then a platform proxy (TRUSTED_PROXIES=REMOTE_ADDR,cloudflare)', function () {
    beforeEach(fn () => webTrust('REMOTE_ADDR,cloudflare'));

    it('is the visitor when the platform appended the Cloudflare edge', function () {
        $ip = webClientIp(['X-Forwarded-For' => '6.6.6.6, 203.0.113.9, '.WEB_CLOUDFLARE_EDGE]);

        expect($ip)->toBe('203.0.113.9');
    });

    it('is the visitor when the platform replaced the chain with the Cloudflare edge', function () {
        $ip = webClientIp(['X-Forwarded-For' => WEB_CLOUDFLARE_EDGE, 'CF-Connecting-IP' => '203.0.113.9']);

        expect($ip)->toBe('203.0.113.9');
    });
});
