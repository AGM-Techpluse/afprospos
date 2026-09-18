<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Identity\Application\Commands\UpdateCustomerAvatarCommand;
use Domain\Identity\Domain\Repositories\CustomerRepository;

final class UpdateCustomerAvatarHandler
{
    public function __construct(
        private readonly CustomerRepository $customers,
        private readonly Atomic $atomic,
    ) {}

    public function handle(UpdateCustomerAvatarCommand $command): void
    {
        $this->atomic->run(fn () => $this->customers->updateAvatar($command->customerId, $command->avatarPath));
    }
}
