<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Identity\Application\Commands\DeactivateStaffAccountCommand;
use Domain\Identity\Application\Contracts\StaffSessionGateway;
use Domain\Identity\Domain\Repositories\StaffRepository;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class DeactivateStaffAccountHandler
{
    public function __construct(
        private readonly StaffRepository $staff,
        private readonly StaffSessionGateway $sessions,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(DeactivateStaffAccountCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $staffId = new StaffId($command->staffId);
            $account = $this->staff->get($staffId);

            $beforeState = ['status' => $account->status()];

            // Domain entity enforces the state-transition invariant
            // (throws StaffAlreadyDeactivated if already inactive).
            $account->deactivate();

            $this->staff->save($account);

            // RBAC-BR-08: preserve history, block future access. The
            // account row/roles/shop grants are NOT deleted — only status
            // flips and the live session(s) are force-invalidated.
            $this->sessions->forceLogoutAllSessions($staffId);

            $this->audit->record(
                module: 'Identity',
                eventType: 'StaffAccountDeactivated',
                actorStaffId: new StaffId($command->deactivatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'staff',
                subjectId: $staffId->value,
                beforeState: $beforeState,
                afterState: ['status' => 'deactivated'],
                context: $command->reason !== null ? ['reason' => $command->reason] : null,
            );
        });
    }
}
