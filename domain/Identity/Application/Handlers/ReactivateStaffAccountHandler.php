<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Identity\Application\Commands\ReactivateStaffAccountCommand;
use Domain\Identity\Domain\Repositories\StaffRepository;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class ReactivateStaffAccountHandler
{
    public function __construct(
        private readonly StaffRepository $staff,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(ReactivateStaffAccountCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $staffId = new StaffId($command->staffId);
            $account = $this->staff->get($staffId);

            $beforeState = ['status' => $account->status()];

            // Domain entity enforces the state-transition invariant
            // (throws StaffAlreadyActive if already active).
            $account->reactivate();

            $this->staff->save($account);

            $this->audit->record(
                module: 'Identity',
                eventType: 'StaffAccountReactivated',
                actorStaffId: new StaffId($command->reactivatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'staff',
                subjectId: $staffId->value,
                beforeState: $beforeState,
                afterState: ['status' => 'active'],
                context: null,
            );
        });
    }
}
