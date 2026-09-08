<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Shared\Domain\Exceptions\PermissionDenied;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level authorization gate (ADD §34's "two levels of defense",
 * level 1). Usage: ->middleware('permission:staff.manage').
 *
 * Must run AFTER ResolveShopContext (ActorContext must already be bound).
 */
final class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $context = app(ActorContext::class);

        if (! $context->hasPermission($permission)) {
            throw PermissionDenied::forPermission($permission);
        }

        return $next($request);
    }
}
