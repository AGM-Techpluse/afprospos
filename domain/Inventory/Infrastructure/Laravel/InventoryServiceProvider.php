<?php

declare(strict_types=1);

namespace Domain\Inventory\Infrastructure\Laravel;

use Domain\Inventory\Application\Contracts\InventoryCatalogQuery;
use Domain\Inventory\Application\Contracts\InventoryReservationService;
use Domain\Inventory\Domain\Repositories\InventoryItemRepository;
use Domain\Inventory\Domain\Repositories\InventoryStockLevelRepository;
use Domain\Inventory\Domain\Repositories\InventoryTransferRepository;
use Domain\Inventory\Domain\Repositories\ProductRepository;
use Domain\Inventory\Domain\Repositories\SkuRepository;
use Domain\Inventory\Infrastructure\Persistence\Repositories\EloquentInventoryItemRepository;
use Domain\Inventory\Infrastructure\Persistence\Repositories\EloquentInventoryStockLevelRepository;
use Domain\Inventory\Infrastructure\Persistence\Repositories\EloquentInventoryTransferRepository;
use Domain\Inventory\Infrastructure\Persistence\Repositories\EloquentProductRepository;
use Domain\Inventory\Infrastructure\Persistence\Repositories\EloquentSkuRepository;
use Illuminate\Support\ServiceProvider;

final class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductRepository::class, EloquentProductRepository::class);
        $this->app->bind(SkuRepository::class, EloquentSkuRepository::class);
        $this->app->bind(InventoryItemRepository::class, EloquentInventoryItemRepository::class);
        $this->app->bind(InventoryStockLevelRepository::class, EloquentInventoryStockLevelRepository::class);
        $this->app->bind(InventoryTransferRepository::class, EloquentInventoryTransferRepository::class);
        $this->app->bind(InventoryReservationService::class, HandlerBackedInventoryReservationService::class);
        $this->app->bind(InventoryCatalogQuery::class, HandlerBackedInventoryCatalogQuery::class);
    }
}
