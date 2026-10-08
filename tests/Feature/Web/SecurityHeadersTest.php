<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Process\Process;

describe('security headers', function () {
    it('are on the public site', function (string $url) {
        $response = $this->get($url);

        $response
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        expect($response->headers->get('Content-Security-Policy'))
            ->toContain("frame-ancestors 'self'", "base-uri 'self'", "object-src 'none'")
            ->and($response->headers->get('Permissions-Policy'))->toContain('camera=()', 'microphone=()');
    })->with(['home' => '/', 'docs' => '/docs', 'robots' => '/robots.txt', 'the admin login' => '/admin/login']);

    it('are on error pages too', function () {
        $this->get('/no-such-page')->assertNotFound()->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    });

    it('do not limit scripts, which the terminal needs inline', function () {
        $csp = $this->get('/')->headers->get('Content-Security-Policy');

        expect($csp)->not->toContain('script-src')->not->toContain('default-src');
    });

    it('only ask for HTTPS-only access over HTTPS', function () {
        $this->get('http://localhost/')->assertHeaderMissing('Strict-Transport-Security');
        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    });

    it('ignore a forwarded protocol when no proxy is trusted', function () {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/')
            ->assertHeaderMissing('Strict-Transport-Security');
    });

    it('treat a request Cloudflare forwarded as HTTPS as secure', function () {
        config(['laravelcloudflare.enabled' => true]);
        Http::fake([
            'www.cloudflare.com/ips-v4' => Http::response("103.21.244.0/22\n"),
            'www.cloudflare.com/ips-v6' => Http::response("2606:4700::/32\n"),
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '103.21.244.5'])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    });

    it('do not clobber a header a response already set', function () {
        Route::get('/_framed', fn () => response('ok')->header('X-Frame-Options', 'DENY'));

        $this->get('/_framed')->assertHeader('X-Frame-Options', 'DENY');
    });
});

/**
 * The cookie's `secure` flag as `config/session.php` computes it for a given environment. `env()` is
 * read once at boot, so this asks a fresh PHP process instead of changing the running one.
 *
 * @param  array<string, string|false>  $env  `false` removes the variable
 */
function webSessionSecureFlag(array $env): bool
{
    $process = new Process(
        [PHP_BINARY, '-r', 'require "vendor/autoload.php"; new Illuminate\Foundation\Application(getcwd()); echo json_encode((require "config/session.php")["secure"]);'],
        base_path(),
        $env,
    );
    $process->mustRun();

    return json_decode($process->getOutput(), true);
}

describe('session cookie', function () {
    it('is marked secure when the site address is https', function () {
        expect(webSessionSecureFlag(['APP_URL' => 'https://apaliampelos.me', 'SESSION_SECURE_COOKIE' => false]))->toBeTrue();
    });

    it('is not forced to secure for a local http site', function () {
        expect(webSessionSecureFlag(['APP_URL' => 'http://localhost', 'SESSION_SECURE_COOKIE' => false]))->toBeFalse();
    });

    it('is not secure when there is no site address at all', function () {
        expect(webSessionSecureFlag(['APP_URL' => false, 'SESSION_SECURE_COOKIE' => false]))->toBeFalse();
    });

    it('can still be set explicitly, whatever the address', function () {
        expect(webSessionSecureFlag(['APP_URL' => 'http://localhost', 'SESSION_SECURE_COOKIE' => 'true']))->toBeTrue()
            ->and(webSessionSecureFlag(['APP_URL' => 'https://apaliampelos.me', 'SESSION_SECURE_COOKIE' => 'false']))->toBeFalse();
    });

    it('is http-only and same-site by default', function () {
        expect(config('session.http_only'))->toBeTrue()
            ->and(config('session.same_site'))->toBe('lax');
    });
});
