<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Repositories;

use Domain\Repair\Domain\ValueObjects\RepairJobId;

/**
 * Records which problem tags a repair job was created with —
 * `labelSnapshot` freezes the tag's label at selection time so a later
 * rename/delete of the catalog entry never changes what an already-created
 * repair job displays.
 */
interface RepairJobProblemTagRepository
{
    public function attach(RepairJobId $repairJobId, ?int $deviceProblemTagId, string $labelSnapshot): void;

    /** @return array<int, array{id: int, device_problem_tag_id: int|null, label_snapshot: string}> */
    public function forJob(RepairJobId $repairJobId): array;
}
