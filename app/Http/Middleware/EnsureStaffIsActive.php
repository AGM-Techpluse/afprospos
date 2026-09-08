<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * BLD RBAC-BR-08 / Phase 1 exit criteria: "a deactivated staff member
 * cannot retain a session or access a shop". Runs on every authenticated
 * admin request — re-checked fresh each time (not cached in the session)
 * so a deactivation made mid-session takes effect on the very next
 * request, regardless of session driver (LaravelStaffSessionGateway's
 * forceLogoutAllSessions() is the immediate-effect path when
 * SESSION_DRIVER=database; this middleware is the guaranteed path
 * either way).
 *
 * Must run AFTER the `auth:staff` middleware and BEFORE ResolveShopContext
 * in the route middleware stack.
 */
final class EnsureStaffIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $staff = Auth::guard('staff')->user();

        if ($staff instanceof StaffRecord && ! $staff->isActive()) {
            Auth::guard('staff')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw new AuthenticationException(
                'This staff account has been deactivated.',
                ['staff'],
            );
        }

        return $next($request);
    }
}
