<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Repositories;

use Domain\Repair\Domain\Entities\RepairJob;
use Domain\Repair\Domain\ValueObjects\RepairJobId;

interface RepairJobRepository
{
    public function get(RepairJobId $id): RepairJob;

    /** Locks the job row with SELECT ... FOR UPDATE — the serialization point for authorization-expiry racing a payment confirmation. */
    public function lockForUpdate(RepairJobId $id): RepairJob;

    public function save(RepairJob $repairJob): RepairJobId;

    /**
     * Unlocked candidate read — jobs whose down-payment/authorization
     * deadline has passed, for the expiry worker to attempt locking one
     * at a time (mirrors SalesCheckoutRepository::findExpirableIds).
     *
     * @return int[]
     */
    public function findExpirableAuthorizationIds(int $limit): array;
}
