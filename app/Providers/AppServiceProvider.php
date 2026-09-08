<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Support\Clock\Clock::class, \App\Support\Clock\SystemClock::class);
        $this->app->singleton(\Domain\Shared\Domain\Contracts\Clock::class, \Domain\Shared\Infrastructure\SystemClock::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
