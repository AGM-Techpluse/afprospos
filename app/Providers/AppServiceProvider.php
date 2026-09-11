<?php

namespace App\Providers;

use App\Support\Clock\Clock;
use App\Support\Clock\SystemClock;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Clock::class, SystemClock::class);
        $this->app->singleton(\Domain\Shared\Domain\Contracts\Clock::class, \Domain\Shared\Infrastructure\SystemClock::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Without this, the `guest:staff`/`guest:customer` middleware falls
        // back to Laravel's default RedirectIfAuthenticated::defaultRedirectUri(),
        // which only knows about a route named `dashboard` or `home` — this
        // app has neither guard-aware, so an already-authenticated staff or
        // customer visiting their own login page was dumped on the generic
        // `home` (Welcome) page instead of their actual dashboard.
        RedirectIfAuthenticated::redirectUsing(function (Request $request): string {
            if (Auth::guard('staff')->check()) {
                return route('admin.dashboard');
            }

            if (Auth::guard('customer')->check()) {
                return route('customer.dashboard');
            }

            return route('home');
        });
    }
}
