<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Contracts;

/**
 * Published for Collection to call once a repaired device is actually
 * released back to the customer (`ReleaseRepairDeviceHandler`) — there's
 * no legitimate reason to keep holding a customer's device passcode/
 * pattern after that point. Collection depends on this Contract, never
 * on Repair's Domain/Infrastructure directly (same shape as Repair's own
 * dependency on `Domain\Repair\Application\Contracts\RepairFinancialStatusQuery`).
 */
interface DeviceLockClearer
{
    public function clear(int $repairJobId, int $clearedByStaffId): void;
}
