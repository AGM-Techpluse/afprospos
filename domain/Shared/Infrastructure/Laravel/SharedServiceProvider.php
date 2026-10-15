<?php

declare(strict_types=1);

namespace Domain\Shared\Infrastructure\Laravel;

use Domain\Shared\Domain\Contracts\SiteSettings;
use Domain\Shared\Infrastructure\Persistence\Repositories\EloquentSiteSettings;
use Illuminate\Support\ServiceProvider;

final class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SiteSettings::class, EloquentSiteSettings::class);
    }
}
