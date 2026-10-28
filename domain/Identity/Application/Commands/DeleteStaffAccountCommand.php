<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Commands;

final readonly class DeleteStaffAccountCommand
{
    public function __construct(
        public int $staffId,
        public int $deletedByStaffId,
    ) {}
}
