<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Handlers;

use Domain\Identity\Application\Commands\AuthenticateStaffCommand;
use Domain\Identity\Application\Contracts\StaffSessionGateway;
use Domain\Identity\Domain\Exceptions\InvalidCredentials;
use Domain\Identity\Domain\Exceptions\StaffAccountDeactivated;
use Domain\Identity\Domain\Repositories\StaffRepository;

final class AuthenticateStaffHandler
{
    public function __construct(
        private readonly StaffRepository $staff,
        private readonly StaffSessionGateway $sessions,
    ) {}

    public function handle(AuthenticateStaffCommand $command): void
    {
        $account = $this->staff->findByEmail($command->email);

        if ($account === null || ! $this->staff->verifyPassword($account->id(), $command->password)) {
            throw InvalidCredentials::make();
        }

        // BLD RBAC-BR-08: checked explicitly and BEFORE establishing a
        // session — a deactivated account must never reach login().
        if (! $account->isActive()) {
            throw StaffAccountDeactivated::forStaff($account->id());
        }

        $this->sessions->login($account->id(), $command->remember);
    }
}
