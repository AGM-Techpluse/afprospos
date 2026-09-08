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
        if ($this->customers->existsWithPhone($command->phone)) {
            throw DuplicateCustomerPhone::forPhone($command->phone);
        }

        $customerId = $this->atomic->run(fn () => $this->customers->create(
            $command->name,
            $command->phone,
            $command->email,
            $command->password,
        ));

        // A newly registered customer is logged in immediately — standard
        // self-service registration UX, matches BRD CUST-01.
        $this->sessions->login($customerId, remember: false);
    }
}
