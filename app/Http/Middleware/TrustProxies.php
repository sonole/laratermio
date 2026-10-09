<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Monicahq\Cloudflare\Http\Middleware\TrustProxies as Middleware;

/**
 * monicahq/laravel-cloudflare's TrustProxies (Cloudflare's ranges, from the cache), plus the proxies
 * that sit on the machine itself: loopback and the server's own address.
 *
 * Plesk runs nginx in front of Apache on the server's public address, so PHP sees the proxy as that
 * address rather than loopback. A connection whose peer is the very machine it arrived on cannot
 * have come from anywhere else, so its forwarded headers are believed without an address to list
 * per server. Anybody else's are ignored, so nothing a visitor sends can choose the address the
 * rate limits key on. Safe wherever there is no such proxy too: no visitor is the machine itself.
 *
 * The same class is in avstelematics/website, avstelematics/api and laratermio: change one, change all.
 */
class TrustProxies extends Middleware
{
    /**
     * @var array<int, string>
     */
    private const array LOOPBACK = ['127.0.0.1', '::1'];

    #[\Override]
    protected function setTrustedProxyIpAddresses(Request $request): void
    {
        parent::setTrustedProxyIpAddresses($request);

        $own = $request->server->get('SERVER_ADDR');

        $this->setTrustedProxyIpAddressesToSpecificIps($request, [
            ...$request::getTrustedProxies(),
            ...self::LOOPBACK,
            ...(is_string($own) && $own !== '' ? [$own] : []),
        ]);
    }
}
