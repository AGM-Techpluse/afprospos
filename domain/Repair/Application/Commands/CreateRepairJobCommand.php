<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class CreateRepairJobCommand
{
    /**
     * @param  int[]  $problemTagIds  DeviceProblemTag ids selected at intake — each gets snapshotted onto the job (RepairJobProblemTagRepository), never referenced live.
     * @param  'none'|'code'|'pattern'  $deviceLockType
     */
    public function __construct(
        public int $shopId,
        public int $customerId,
        public string $deviceMake,
        public string $deviceModel,
        public ?string $reportedIssue,
        public ?string $deviceImeiSerial,
        public string $deviceLockType,
        public ?string $deviceLockValue,
        public array $problemTagIds,
        public int $labourChargeMinor,
        public int $createdByStaffId,
    ) {}
}
