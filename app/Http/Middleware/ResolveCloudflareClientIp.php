<?php

namespace App\Http\Middleware;

use App\Support\TrustedProxies;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes `$request->ip()` the visitor rather than a Cloudflare edge address.
 *
 * Runs after TrustProxies, so by now `ip()` is the nearest address in the forwarded chain that is
 * not a trusted proxy, which a visitor cannot forge. When that address is a Cloudflare edge the
 * request really came through Cloudflare, which always overwrites `CF-Connecting-IP` with the
 * visitor's address. It only takes effect once the proxies in front are trusted (TRUSTED_PROXIES);
 * without a Cloudflare edge in the chain it does nothing at all.
 */
class ResolveCloudflareClientIp
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $visitor = $request->headers->get('CF-Connecting-IP');

        if (is_string($visitor)
            && filter_var($visitor, FILTER_VALIDATE_IP)
            && IpUtils::checkIp((string) $request->ip(), TrustedProxies::CLOUDFLARE)) {
            // Symfony drops the trusted caller from the chain and takes what is left.
            $request->headers->set('X-Forwarded-For', $visitor);
        }

        return $next($request);
    }
}
