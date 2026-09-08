<?php

declare(strict_types=1);

namespace App\Providers;

use Domain\Audit\Infrastructure\Laravel\AuditServiceProvider;
use Domain\Identity\Infrastructure\Laravel\IdentityServiceProvider;
use Domain\RBAC\Infrastructure\Laravel\RbacServiceProvider;
use Domain\Shop\Infrastructure\Laravel\ShopServiceProvider;
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
    }
}
