<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Contracts;

/**
 * The published cross-module read contract — Collection depends on this
 * (and only this) to decide whether a device may be released, never on
 * Repair's Domain/Infrastructure internals directly (CPNC §4.2).
 */
interface RepairFinancialStatusQuery
{
    /** @return 'unpaid'|'partially_paid'|'fully_paid' */
    public function financialStatusFor(int $repairJobId): string;
}
