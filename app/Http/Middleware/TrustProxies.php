<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    /**
     * Trust all proxies (use with caution). Render and many cloud providers
     * terminate TLS at the load balancer and forward the original scheme
     * via X-Forwarded-* headers. Setting proxies to '*' tells Laravel to
     * trust those headers.
     *
     * If you prefer to list specific proxies, replace '*' with an array of
     * IPs/CIDRs.
     *
     * @var array|string|null
     */
    protected $proxies = '*';

    /**
     * Use the combined forwarded header bits so Laravel reads forwarded
     * headers (including X-Forwarded-Proto). Keep explicit bitmask to
     * avoid relying on HEADER_X_FORWARDED_ALL constant availability.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
