<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class UpdateQcFailPolicyCommand
{
    public function __construct(
        public string $action,
        public int $updatedByStaffId,
    ) {}
}
