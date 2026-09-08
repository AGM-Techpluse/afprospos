<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Commands;

final readonly class DeactivateStaffAccountCommand
{
    public function __construct(
        public int $staffId,
        public int $deactivatedByStaffId,
        public ?string $reason,
    ) {}
}
