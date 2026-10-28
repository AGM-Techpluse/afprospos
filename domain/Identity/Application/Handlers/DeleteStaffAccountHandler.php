<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Identity\Application\Commands\DeleteStaffAccountCommand;
use Domain\Identity\Application\Contracts\StaffSessionGateway;
use Domain\Identity\Domain\Repositories\StaffRepository;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class DeleteStaffAccountHandler
{
    public function __construct(
        private readonly StaffRepository $staff,
        private readonly StaffSessionGateway $sessions,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(DeleteStaffAccountCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $staffId = new StaffId($command->staffId);
            $account = $this->staff->get($staffId);

            $beforeState = [
                'name' => $account->name(),
                'email' => $account->email(),
                'status' => $account->status(),
            ];

            $this->sessions->forceLogoutAllSessions($staffId);

            // Throws StaffHasExistingRecords (via a FK violation translated
            // in the repository) if any business record still references
            // this staff id — the audit write below never runs in that
            // case, and the whole transaction rolls back.
            $this->staff->delete($staffId);

            $this->audit->record(
                module: 'Identity',
                eventType: 'StaffAccountDeleted',
                actorStaffId: new StaffId($command->deletedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'staff',
                subjectId: $staffId->value,
                beforeState: $beforeState,
                afterState: null,
                context: null,
            );
        });
    }
}
