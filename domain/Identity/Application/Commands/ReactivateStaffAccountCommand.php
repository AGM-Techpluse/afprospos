<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Commands;

final readonly class ReactivateStaffAccountCommand
{
    public function __construct(
        public int $staffId,
        public int $reactivatedByStaffId,
    ) {}
}
