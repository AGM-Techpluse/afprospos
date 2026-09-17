<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Laravel;

use Domain\Repair\Application\Contracts\DeviceLockClearer;
use Domain\Repair\Application\Contracts\RepairFinancialStatusQuery;
use Domain\Repair\Application\Contracts\RepairJobLookup;
use Domain\Repair\Domain\Repositories\DeviceBrandRepository;
use Domain\Repair\Domain\Repositories\DeviceProblemSuggestedPartRepository;
use Domain\Repair\Domain\Repositories\DeviceProblemTagRepository;
use Domain\Repair\Domain\Repositories\DeviceTypeRepository;
use Domain\Repair\Domain\Repositories\RepairDiagnosisRepository;
use Domain\Repair\Domain\Repositories\RepairJobProblemTagRepository;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\Repositories\RepairPartReservationRepository;
use Domain\Repair\Domain\Repositories\RepairPhotoRepository;
use Domain\Repair\Infrastructure\Persistence\Repositories\EloquentDeviceBrandRepository;
use Domain\Repair\Infrastructure\Persistence\Repositories\EloquentDeviceProblemSuggestedPartRepository;
use Domain\Repair\Infrastructure\Persistence\Repositories\EloquentDeviceProblemTagRepository;
use Domain\Repair\Infrastructure\Persistence\Repositories\EloquentDeviceTypeRepository;
use Domain\Repair\Infrastructure\Persistence\Repositories\EloquentRepairDiagnosisRepository;
use Domain\Repair\Infrastructure\Persistence\Repositories\EloquentRepairJobProblemTagRepository;
use Domain\Repair\Infrastructure\Persistence\Repositories\EloquentRepairJobRepository;
use Domain\Repair\Infrastructure\Persistence\Repositories\EloquentRepairPartReservationRepository;
use Domain\Repair\Infrastructure\Persistence\Repositories\EloquentRepairPhotoRepository;
use Illuminate\Support\ServiceProvider;

final class RepairServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RepairJobRepository::class, EloquentRepairJobRepository::class);
        $this->app->bind(RepairDiagnosisRepository::class, EloquentRepairDiagnosisRepository::class);
        $this->app->bind(RepairPartReservationRepository::class, EloquentRepairPartReservationRepository::class);
        $this->app->bind(RepairFinancialStatusQuery::class, EloquentRepairFinancialStatusQuery::class);
        $this->app->bind(DeviceTypeRepository::class, EloquentDeviceTypeRepository::class);
        $this->app->bind(DeviceBrandRepository::class, EloquentDeviceBrandRepository::class);
        $this->app->bind(DeviceProblemTagRepository::class, EloquentDeviceProblemTagRepository::class);
        $this->app->bind(DeviceProblemSuggestedPartRepository::class, EloquentDeviceProblemSuggestedPartRepository::class);
        $this->app->bind(RepairJobProblemTagRepository::class, EloquentRepairJobProblemTagRepository::class);
        $this->app->bind(DeviceLockClearer::class, EloquentDeviceLockClearer::class);
        $this->app->bind(RepairJobLookup::class, EloquentRepairJobLookup::class);
        $this->app->bind(RepairPhotoRepository::class, EloquentRepairPhotoRepository::class);
    }
}
