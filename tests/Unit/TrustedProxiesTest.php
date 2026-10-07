<?php

use App\Support\TrustedProxies;
use Symfony\Component\HttpFoundation\IpUtils;

describe('TRUSTED_PROXIES', function () {
    it('trusts nothing when empty, missing or not a name', function (mixed $value) {
        expect(TrustedProxies::fromEnv($value))->toBeNull();
    })->with([null, '', '   ', ' , ,', true, false]);

    it('passes a single word or address through', function () {
        expect(TrustedProxies::fromEnv('REMOTE_ADDR'))->toBe(['REMOTE_ADDR'])
            ->and(TrustedProxies::fromEnv('10.0.0.5'))->toBe(['10.0.0.5']);
    });

    it('splits a list and trims the spaces', function () {
        expect(TrustedProxies::fromEnv(' 10.0.0.0/8 ,172.16.0.5,  REMOTE_ADDR '))
            ->toBe(['10.0.0.0/8', '172.16.0.5', 'REMOTE_ADDR']);
    });

    it('expands the word cloudflare into its ranges, in any case', function () {
        expect(TrustedProxies::fromEnv('cloudflare'))->toBe(TrustedProxies::CLOUDFLARE)
            ->and(TrustedProxies::fromEnv('Cloudflare'))->toBe(TrustedProxies::CLOUDFLARE);
    });

    it('combines the platform proxy with Cloudflare', function () {
        $proxies = TrustedProxies::fromEnv('REMOTE_ADDR,cloudflare');

        expect($proxies[0])->toBe('REMOTE_ADDR')
            ->and(array_slice($proxies, 1))->toBe(TrustedProxies::CLOUDFLARE);
    });

    it('drops duplicates', function () {
        expect(TrustedProxies::fromEnv('REMOTE_ADDR,REMOTE_ADDR,10.0.0.5,10.0.0.5'))->toBe(['REMOTE_ADDR', '10.0.0.5']);
    });

    it('passes a wildcard through as the single word Laravel understands', function (string $value) {
        expect(TrustedProxies::fromEnv($value))->toBe('*');
    })->with(['*', '**', 'REMOTE_ADDR,*']);
});

describe('Cloudflare ranges', function () {
    it('are all valid IPv4 or IPv6 ranges', function (string $range) {
        [$address, $bits] = explode('/', $range);

        expect(filter_var($address, FILTER_VALIDATE_IP))->not->toBeFalse()
            ->and((int) $bits)->toBeGreaterThan(0)
            ->and(IpUtils::checkIp($address, $range))->toBeTrue();
    })->with(fn () => TrustedProxies::CLOUDFLARE);

    it('recognise a Cloudflare address and not a private or other public one', function () {
        expect(IpUtils::checkIp('103.21.244.5', TrustedProxies::CLOUDFLARE))->toBeTrue()
            ->and(IpUtils::checkIp('2606:4700::1234', TrustedProxies::CLOUDFLARE))->toBeTrue()
            ->and(IpUtils::checkIp('198.51.100.7', TrustedProxies::CLOUDFLARE))->toBeFalse()
            ->and(IpUtils::checkIp('10.0.0.1', TrustedProxies::CLOUDFLARE))->toBeFalse();
    });
});
