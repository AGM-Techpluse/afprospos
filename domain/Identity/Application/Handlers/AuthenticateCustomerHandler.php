<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Handlers;

use Domain\Identity\Application\Commands\AuthenticateCustomerCommand;
use Domain\Identity\Application\Contracts\CustomerSessionGateway;
use Domain\Identity\Domain\Exceptions\InvalidCredentials;
use Domain\Identity\Domain\Repositories\CustomerRepository;

final class AuthenticateCustomerHandler
{
    public function __construct(
        private readonly CustomerRepository $customers,
        private readonly CustomerSessionGateway $sessions,
    ) {}

    public function handle(AuthenticateCustomerCommand $command): void
    {
        $account = $this->customers->findByPhone($command->phone);

        if ($account === null || ! $this->customers->verifyPassword($account->id(), $command->password)) {
            throw InvalidCredentials::make();
        }

        $this->sessions->login($account->id(), $command->remember);
    }
}
