<?php

declare(strict_types=1);

namespace Domain\Commission\Application\Contracts;

use Domain\Commission\Application\Commands\RecordCommissionEarnedCommand;

/**
 * The published cross-module write contract (mirrors InventoryReservationService's
 * role) — Repair and Sales record commission through this, never by reaching into
 * Commission's own Domain/Infrastructure directly (CPNC §4.3).
 */
interface CommissionRecordingService
{
    public function recordEarned(RecordCommissionEarnedCommand $command): void;
}
