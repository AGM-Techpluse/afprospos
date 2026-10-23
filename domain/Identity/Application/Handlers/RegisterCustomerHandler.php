<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Identity\Application\Commands\RegisterCustomerCommand;
use Domain\Identity\Application\Contracts\CustomerSessionGateway;
use Domain\Identity\Domain\Exceptions\DuplicateCustomerPhone;
use Domain\Identity\Domain\Repositories\CustomerRepository;

final class RegisterCustomerHandler
{
    public function __construct(
        private readonly CustomerRepository $customers,
        private readonly CustomerSessionGateway $sessions,
        private readonly Atomic $atomic,
    ) {}

    public function handle(RegisterCustomerCommand $command): void
    {
        $existing = $this->customers->findByPhone($command->phone);

        if ($existing !== null && $existing->isRegistered()) {
            throw DuplicateCustomerPhone::forPhone($command->phone);
        }

        $customerId = $this->atomic->run(function () use ($command, $existing) {
            if ($existing !== null) {
                // A walk-in row already exists for this phone (recorded during a past sale/repair/diagnosis) — claim it instead of rejecting a legitimate registration as a "duplicate."
                $this->customers->claimWalkIn($existing->id(), $command->name, $command->email, $command->password);

                return $existing->id();
            }

            return $this->customers->create($command->name, $command->phone, $command->email, $command->password);
        });

        // A newly registered customer is logged in immediately — standard
        // self-service registration UX, matches BRD CUST-01.
        $this->sessions->login($customerId, remember: false);
    }
}
