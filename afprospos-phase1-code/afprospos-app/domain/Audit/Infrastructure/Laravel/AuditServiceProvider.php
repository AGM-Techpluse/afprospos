<?php

declare(strict_types=1);

namespace Domain\Audit\Infrastructure\Laravel;

use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Audit\Infrastructure\Persistence\EloquentAuditWriter;
use Illuminate\Support\ServiceProvider;

final class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuditWriter::class, EloquentAuditWriter::class);
    }
}
