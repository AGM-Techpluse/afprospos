<?php

declare(strict_types=1);

namespace Domain\Shop\Infrastructure\Laravel;

use Domain\Shop\Application\Contracts\ActiveShopSessionStore;
use Domain\Shop\Domain\Repositories\ShopRepository;
use Domain\Shop\Infrastructure\Persistence\Repositories\EloquentShopRepository;
use Illuminate\Support\ServiceProvider;

final class ShopServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ShopRepository::class, EloquentShopRepository::class);
        $this->app->bind(ActiveShopSessionStore::class, LaravelActiveShopSessionStore::class);
    }
}
