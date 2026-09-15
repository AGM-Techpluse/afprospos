<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class CreateRepairJobCommand
{
    public function __construct(
        public int $shopId,
        public int $customerId,
        public string $deviceMake,
        public string $deviceModel,
        public int $labourChargeMinor,
        public int $createdByStaffId,
    ) {}
}
