<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Handlers;

use Domain\Identity\Application\Commands\LogoutCustomerCommand;
use Domain\Identity\Application\Contracts\CustomerSessionGateway;

final class LogoutCustomerHandler
{
    public function __construct(private readonly CustomerSessionGateway $sessions) {}

    public function handle(LogoutCustomerCommand $command): void
    {
        $this->sessions->logout();
    }
}
