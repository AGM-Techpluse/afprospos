<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Handlers;

use Domain\Identity\Application\Commands\LogoutStaffCommand;
use Domain\Identity\Application\Contracts\StaffSessionGateway;

final class LogoutStaffHandler
{
    public function __construct(private readonly StaffSessionGateway $sessions) {}

    public function handle(LogoutStaffCommand $command): void
    {
        $this->sessions->logout();
    }
}
