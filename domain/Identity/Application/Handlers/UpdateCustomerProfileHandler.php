<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Identity\Application\Commands\UpdateCustomerProfileCommand;
use Domain\Identity\Domain\Exceptions\DuplicateCustomerEmail;
use Domain\Identity\Domain\Exceptions\DuplicateCustomerPhone;
use Domain\Identity\Domain\Repositories\CustomerRepository;

final class UpdateCustomerProfileHandler
{
    public function __construct(
        private readonly CustomerRepository $customers,
        private readonly Atomic $atomic,
    ) {}

    public function handle(UpdateCustomerProfileCommand $command): void
    {
        if ($this->customers->existsWithPhoneExcept($command->phone, $command->customerId)) {
            throw DuplicateCustomerPhone::forPhone($command->phone);
        }

        if ($command->email !== null && $this->customers->existsWithEmailExcept($command->email, $command->customerId)) {
            throw DuplicateCustomerEmail::forEmail($command->email);
        }

        $this->atomic->run(fn () => $this->customers->updateProfile(
            $command->customerId,
            $command->name,
            $command->phone,
            $command->email,
        ));
    }
}
