<?php

declare(strict_types=1);

namespace Domain\Notifications\Application\Commands;

final readonly class MarkAllStaffNotificationsReadCommand
{
    public function __construct(
        public int $staffId,
    ) {}
}
