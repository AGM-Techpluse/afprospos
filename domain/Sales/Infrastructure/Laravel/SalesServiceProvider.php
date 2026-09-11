<?php

declare(strict_types=1);

namespace Domain\Sales\Infrastructure\Laravel;

use Domain\Sales\Domain\Repositories\SaleRepository;
use Domain\Sales\Domain\Repositories\SalesCheckoutRepository;
use Domain\Sales\Infrastructure\Persistence\Repositories\EloquentSaleRepository;
use Domain\Sales\Infrastructure\Persistence\Repositories\EloquentSalesCheckoutRepository;
use Illuminate\Support\ServiceProvider;

final class SalesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SalesCheckoutRepository::class, EloquentSalesCheckoutRepository::class);
        $this->app->bind(SaleRepository::class, EloquentSaleRepository::class);
    }
}
