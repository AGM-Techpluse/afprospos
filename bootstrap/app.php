<?php

use App\Http\Middleware\EnsureStaffIsActive;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolveShopContext;
use Domain\Shared\Domain\Exceptions\ShopScopeViolation;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
            Route::middleware('web')
                ->group(base_path('routes/customer.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Without this, HandleInertiaRequests never runs: Laravel's default
        // `web` group does not include it, and Inertia's installer normally
        // appends it here. Its absence meant NO shared Inertia data ever
        // reached any page — not validation `errors`, not `app_name`/
        // `app_logo`, not the `auth.customer`/`auth.staff` data added later —
        // while page-specific props (passed directly to Inertia::render())
        // kept working, which is why this went unnoticed for a while.
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'staff.active' => EnsureStaffIsActive::class,
            'shop.context' => ResolveShopContext::class,
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin') || $request->is('admin/*') || $request->is('staff/*')) {
                return route('staff.login');
            }
            if ($request->is('customer/*')) {
                return route('customer.login');
            }

            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ShopScopeViolation $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 403);
            }

            return abort(403, $e->getMessage());
        });

        // A friendly, on-brand full-page error (403/404/419/429/500/503) instead of
        // Laravel's raw "{status} | {message}" fallback — see resources/js/Pages/Error.tsx.
        // Never applies to API/JSON requests, which keep Laravel's normal JSON error shape.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return null;
            }

            $status = $e->getStatusCode();

            if (! in_array($status, [403, 404, 419, 429, 500, 503], true)) {
                return null;
            }

            $theme = $request->is('customer/*') ? 'customer' : 'admin';

            return Inertia::render('Error', ['status' => $status, 'theme' => $theme])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();
