<?php

declare(strict_types=1);

namespace Domain\Commission\Infrastructure\Laravel;

use Domain\Commission\Application\Commands\RecordCommissionEarnedCommand;
use Domain\Commission\Application\Contracts\CommissionRecordingService;
use Domain\Commission\Application\Handlers\RecordCommissionEarnedHandler;

final class HandlerBackedCommissionRecordingService implements CommissionRecordingService
{
    public function __construct(private readonly RecordCommissionEarnedHandler $handler) {}

    public function recordEarned(RecordCommissionEarnedCommand $command): void
    {
        $this->handler->handle($command);
    }
}
