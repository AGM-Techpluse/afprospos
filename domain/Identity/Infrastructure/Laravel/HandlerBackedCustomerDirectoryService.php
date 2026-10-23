<?php

declare(strict_types=1);

namespace Domain\Identity\Infrastructure\Laravel;

use Domain\Identity\Application\Commands\FindOrCreateWalkInCustomerCommand;
use Domain\Identity\Application\Contracts\CustomerDirectoryService;
use Domain\Identity\Application\Handlers\FindOrCreateWalkInCustomerHandler;

final class HandlerBackedCustomerDirectoryService implements CustomerDirectoryService
{
    public function __construct(private readonly FindOrCreateWalkInCustomerHandler $handler) {}

    public function findOrCreateWalkIn(string $name, string $phone, ?string $email): int
    {
        return $this->handler->handle(new FindOrCreateWalkInCustomerCommand($name, $phone, $email))->value;
    }
}
