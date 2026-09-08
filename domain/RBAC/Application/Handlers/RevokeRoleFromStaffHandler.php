<?php

declare(strict_types=1);

namespace Domain\RBAC\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\RBAC\Application\Commands\RevokeRoleFromStaffCommand;
use Domain\RBAC\Domain\Repositories\StaffRoleAssignmentRepository;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class RevokeRoleFromStaffHandler
{
    public function __construct(
        private readonly StaffRoleAssignmentRepository $roles,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(RevokeRoleFromStaffCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $staffId = new StaffId($command->staffId);

            $before = $this->roles->currentRoleNames($staffId);
            $this->roles->revokeRole($staffId, $command->roleName);
            $after = $this->roles->currentRoleNames($staffId);

            $this->audit->record(
                module: 'RBAC',
                eventType: 'RoleRevokedFromStaff',
                actorStaffId: new StaffId($command->revokedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'staff',
                subjectId: $staffId->value,
                beforeState: ['roles' => $before],
                afterState: ['roles' => $after],
            );
        });
    }
}
