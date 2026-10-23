<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Handlers;

use Domain\Identity\Application\Commands\FindOrCreateWalkInCustomerCommand;
use Domain\Identity\Domain\Repositories\CustomerRepository;
use Domain\Shared\Domain\ValueObjects\CustomerId;

/**
 * Links a staff-captured walk-in (a sale/repair/diagnosis intake's bare
 * name+phone) to a real `CustomerRecord`, so the same person is "callable
 * up" on their next visit — a found row's own name/email are left alone
 * (never overwritten by a hastily-typed walk-in name), unlike claiming a
 * walk-in via self-service registration, where the person's own input is
 * authoritative.
 */
final class FindOrCreateWalkInCustomerHandler
{
    public function __construct(private readonly CustomerRepository $customers) {}

    public function handle(FindOrCreateWalkInCustomerCommand $command): CustomerId
    {
        $existing = $this->customers->findByPhone($command->phone);

        if ($existing !== null) {
            return $existing->id();
        }

        return $this->customers->createWalkIn($command->name, $command->phone, $command->email);
    }
}
