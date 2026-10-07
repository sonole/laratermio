<?php

use App\Support\TrustedProxies;

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted proxies
    |--------------------------------------------------------------------------
    |
    | Read by Laravel's TrustProxies middleware. Leave `TRUSTED_PROXIES` empty when nothing sits in
    | front of the app. Behind a reverse proxy, load balancer or CDN, set it, or the app sees the
    | proxy's address instead of the visitor's and thinks every request is plain HTTP:
    |
    |   TRUSTED_PROXIES=REMOTE_ADDR              one proxy or platform in front (Railway, Render, Fly, Traefik...)
    |   TRUSTED_PROXIES=10.0.0.0/8,172.16.0.5    your own proxies, by address or range
    |   TRUSTED_PROXIES=cloudflare               Cloudflare straight to your server
    |   TRUSTED_PROXIES=REMOTE_ADDR,cloudflare   Cloudflare, then a platform's proxy, then the app
    |
    | See App\Support\TrustedProxies.
    |
    */

    'proxies' => TrustedProxies::fromEnv(env('TRUSTED_PROXIES')),

];
