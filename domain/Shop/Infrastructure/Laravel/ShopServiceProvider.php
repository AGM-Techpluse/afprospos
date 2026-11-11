<?php

declare(strict_types=1);

namespace Domain\Shop\Infrastructure\Laravel;

use Domain\Shared\Domain\Contracts\ShopClassification;
use Domain\Shop\Application\Contracts\ActiveShopSessionStore;
use Domain\Shop\Application\Queries\AccessibleShopsQuery;
use Domain\Shop\Domain\Repositories\ShopRepository;
use Domain\Shop\Infrastructure\Persistence\Repositories\EloquentShopRepository;
use Illuminate\Support\ServiceProvider;

final class ShopServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ShopRepository::class, EloquentShopRepository::class);
        $this->app->bind(ShopClassification::class, EloquentShopClassification::class);
        $this->app->bind(ActiveShopSessionStore::class, LaravelActiveShopSessionStore::class);
        // Singleton so its per-request memoization actually shares one cache across
        // every consumer — see the class's own docblock for why that matters here.
        $this->app->singleton(AccessibleShopsQuery::class);
    }
}
