<?php

namespace App\Support;

/**
 * Which proxies in front of the app may be believed about the visitor's address and the scheme.
 *
 * A request's forwarded headers (`X-Forwarded-For`, `X-Forwarded-Proto`, ...) are only as honest
 * as whoever wrote them. With no proxy in front, the visitor wrote them, so nothing is trusted.
 * Behind a reverse proxy or load balancer, that proxy has to be named so its headers count.
 */
class TrustedProxies
{
    /**
     * Cloudflare's edge ranges, for `TRUSTED_PROXIES=cloudflare`.
     * Source: https://www.cloudflare.com/ips-v4 and /ips-v6 (fetched 2026-10-07). They change rarely.
     *
     * @var list<string>
     */
    public const array CLOUDFLARE = [
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    ];

    /**
     * Turn the `TRUSTED_PROXIES` setting into what Laravel's TrustProxies middleware accepts.
     *
     * A comma-separated list of IP addresses or CIDR ranges, plus two words:
     *  - `REMOTE_ADDR`: whoever is connecting to the app right now (the platform's proxy). Only safe
     *    when the app cannot be reached except through that proxy.
     *  - `cloudflare`: Cloudflare's edge ranges.
     * Empty trusts nothing, which is right when there is no proxy. `*` trusts every address, which
     * lets a visitor choose their own IP; it is passed through but should not be used.
     *
     * @return list<string>|string|null
     */
    public static function fromEnv(mixed $value): array|string|null
    {
        // `env()` turns a literal true/false into a boolean; neither names a proxy.
        $value = is_string($value) ? $value : '';

        $tokens = array_values(array_filter(array_map(trim(...), explode(',', $value)), fn (string $token) => $token !== ''));

        if ($tokens === []) {
            return null;
        }

        if (in_array('*', $tokens, true) || in_array('**', $tokens, true)) {
            return '*';
        }

        $proxies = [];

        foreach ($tokens as $token) {
            array_push($proxies, ...(strtolower($token) === 'cloudflare' ? self::CLOUDFLARE : [$token]));
        }

        return array_values(array_unique($proxies));
    }
}
