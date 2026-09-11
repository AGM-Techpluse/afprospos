<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Commands;

final readonly class UpdateStaffProfileCommand
{
    public function __construct(
        public int $staffId,
        public string $name,
        public string $phone,
        public string $email,
        public int $updatedByStaffId,
    ) {}
}
