<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Repositories;

use Domain\Repair\Domain\Entities\RepairPartReservation;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Repair\Domain\ValueObjects\RepairPartReservationId;

interface RepairPartReservationRepository
{
    /** @return RepairPartReservation[] */
    public function findByRepairJob(RepairJobId $repairJobId): array;

    public function lockForUpdate(RepairPartReservationId $id): RepairPartReservation;

    public function save(RepairPartReservation $reservation): RepairPartReservationId;
}
