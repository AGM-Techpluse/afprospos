<?php

declare(strict_types=1);

namespace App\Providers;

use Domain\Audit\Infrastructure\Laravel\AuditServiceProvider;
use Domain\Collection\Infrastructure\Laravel\CollectionServiceProvider;
use Domain\Identity\Infrastructure\Laravel\IdentityServiceProvider;
use Domain\Inventory\Infrastructure\Laravel\InventoryServiceProvider;
use Domain\Notifications\Infrastructure\Laravel\NotificationsServiceProvider;
use Domain\Payments\Infrastructure\Laravel\PaymentsServiceProvider;
use Domain\RBAC\Infrastructure\Laravel\RbacServiceProvider;
use Domain\Repair\Infrastructure\Laravel\RepairServiceProvider;
use Domain\Sales\Infrastructure\Laravel\SalesServiceProvider;
use Domain\Shop\Infrastructure\Laravel\ShopServiceProvider;
use Domain\Warranty\Infrastructure\Laravel\WarrantyServiceProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Registers every bounded-context service provider. As new modules land
 * in later phases (Inventory, Sales, Payments, ...), register their
 * providers here too — this is the single place that answers "which
 * modules does this application actually wire up".
 */
final class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(AuditServiceProvider::class);
        $this->app->register(IdentityServiceProvider::class);
        $this->app->register(RbacServiceProvider::class);
        $this->app->register(ShopServiceProvider::class);
        $this->app->register(InventoryServiceProvider::class);
        $this->app->register(PaymentsServiceProvider::class);
        $this->app->register(CollectionServiceProvider::class);
        $this->app->register(RepairServiceProvider::class);
        $this->app->register(SalesServiceProvider::class);
        $this->app->register(WarrantyServiceProvider::class);
        $this->app->register(NotificationsServiceProvider::class);
    }
}
