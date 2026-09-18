<?php

declare(strict_types=1);

namespace Domain\Warranty\Infrastructure\Laravel;

use Domain\Warranty\Domain\Repositories\ReturnRequestRepository;
use Domain\Warranty\Domain\Repositories\TradeInAssessmentRepository;
use Domain\Warranty\Domain\Repositories\WarrantyClaimRepository;
use Domain\Warranty\Domain\Repositories\WarrantyPolicyRepository;
use Domain\Warranty\Infrastructure\Persistence\Repositories\EloquentReturnRequestRepository;
use Domain\Warranty\Infrastructure\Persistence\Repositories\EloquentTradeInAssessmentRepository;
use Domain\Warranty\Infrastructure\Persistence\Repositories\EloquentWarrantyClaimRepository;
use Domain\Warranty\Infrastructure\Persistence\Repositories\EloquentWarrantyPolicyRepository;
use Illuminate\Support\ServiceProvider;

final class WarrantyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WarrantyPolicyRepository::class, EloquentWarrantyPolicyRepository::class);
        $this->app->bind(WarrantyClaimRepository::class, EloquentWarrantyClaimRepository::class);
        $this->app->bind(ReturnRequestRepository::class, EloquentReturnRequestRepository::class);
        $this->app->bind(TradeInAssessmentRepository::class, EloquentTradeInAssessmentRepository::class);
    }
}
