<?php

declare(strict_types=1);

namespace Domain\Warranty\Infrastructure\Laravel;

use Domain\Warranty\Domain\Repositories\WarrantyClaimRepository;
use Domain\Warranty\Domain\Repositories\WarrantyPolicyRepository;
use Domain\Warranty\Infrastructure\Persistence\Repositories\EloquentWarrantyClaimRepository;
use Domain\Warranty\Infrastructure\Persistence\Repositories\EloquentWarrantyPolicyRepository;
use Illuminate\Support\ServiceProvider;

final class WarrantyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WarrantyPolicyRepository::class, EloquentWarrantyPolicyRepository::class);
        $this->app->bind(WarrantyClaimRepository::class, EloquentWarrantyClaimRepository::class);
    }
}
