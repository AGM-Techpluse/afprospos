<?php

declare(strict_types=1);

namespace Domain\Commission\Infrastructure\Laravel;

use Domain\Commission\Application\Contracts\CommissionRecordingService;
use Domain\Commission\Domain\Repositories\CommissionLedgerEntryRepository;
use Domain\Commission\Infrastructure\Persistence\Repositories\EloquentCommissionLedgerEntryRepository;
use Illuminate\Support\ServiceProvider;

final class CommissionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CommissionLedgerEntryRepository::class, EloquentCommissionLedgerEntryRepository::class);
        $this->app->bind(CommissionRecordingService::class, HandlerBackedCommissionRecordingService::class);
    }
}
