<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Laravel;

use Domain\Repair\Application\Contracts\RepairFinancialStatusQuery;
use Domain\Repair\Domain\Repositories\RepairDiagnosisRepository;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\Repositories\RepairPartReservationRepository;
use Domain\Repair\Infrastructure\Persistence\Repositories\EloquentRepairDiagnosisRepository;
use Domain\Repair\Infrastructure\Persistence\Repositories\EloquentRepairJobRepository;
use Domain\Repair\Infrastructure\Persistence\Repositories\EloquentRepairPartReservationRepository;
use Illuminate\Support\ServiceProvider;

final class RepairServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RepairJobRepository::class, EloquentRepairJobRepository::class);
        $this->app->bind(RepairDiagnosisRepository::class, EloquentRepairDiagnosisRepository::class);
        $this->app->bind(RepairPartReservationRepository::class, EloquentRepairPartReservationRepository::class);
        $this->app->bind(RepairFinancialStatusQuery::class, EloquentRepairFinancialStatusQuery::class);
    }
}
