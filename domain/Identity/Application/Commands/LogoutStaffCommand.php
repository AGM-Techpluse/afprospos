<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Commands;

final readonly class LogoutStaffCommand
{
    public function __construct(
        public ?int $staffId = null,
        public ?string $ipAddress = null,
    ) {}
}
