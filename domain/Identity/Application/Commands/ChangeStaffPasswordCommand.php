<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Commands;

final readonly class ChangeStaffPasswordCommand
{
    public function __construct(
        public int $staffId,
        public string $currentPassword,
        public string $newPassword,
    ) {}
}
