<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the public storefront (`/shop`, `/shop/{sku}`, and the customer
 * self-service cart) behind `afprospos.storefront_enabled`
 * (STOREFRONT_ENABLED in .env) — a plain 404, not a "feature disabled"
 * message, so a switched-off storefront looks like it was never there
 * rather than advertising a flag. Nothing else under /customer is
 * gated — orders/payments/warranty/repairs stay reachable either way.
 */
final class EnsureStorefrontEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless((bool) config('afprospos.storefront_enabled'), 404);

        return $next($request);
    }
}
