<?php

declare(strict_types=1);

namespace Domain\RBAC\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\RBAC\Application\Commands\UpdateRolePermissionsCommand;
use Domain\RBAC\Domain\Exceptions\UnknownRole;
use Domain\RBAC\Domain\Repositories\RoleRepository;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class UpdateRolePermissionsHandler
{
    public function __construct(
        private readonly RoleRepository $roles,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(UpdateRolePermissionsCommand $command): void
    {
        $before = $this->roles->find($command->name);

        if ($before === null) {
            throw UnknownRole::named($command->name);
        }

        $this->atomic->run(function () use ($command, $before): void {
            $this->roles->updatePermissions($command->name, $command->permissionNames);

            $this->audit->record(
                module: 'RBAC',
                eventType: 'RolePermissionsUpdated',
                actorStaffId: new StaffId($command->updatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'role',
                subjectId: $before['id'],
                beforeState: ['permissions' => $before['permissionNames']],
                afterState: ['permissions' => $command->permissionNames],
            );
        });
    }
}
