<?php

declare(strict_types=1);

namespace Domain\RBAC\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\RBAC\Application\Commands\CreateRoleCommand;
use Domain\RBAC\Domain\Exceptions\DuplicateRoleName;
use Domain\RBAC\Domain\Repositories\RoleRepository;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class CreateRoleHandler
{
    public function __construct(
        private readonly RoleRepository $roles,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(CreateRoleCommand $command): void
    {
        if ($this->roles->existsWithName($command->name)) {
            throw DuplicateRoleName::forName($command->name);
        }

        $this->atomic->run(function () use ($command): void {
            $roleId = $this->roles->create($command->name, $command->permissionNames);

            $this->audit->record(
                module: 'RBAC',
                eventType: 'RoleCreated',
                actorStaffId: new StaffId($command->createdByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'role',
                subjectId: $roleId,
                beforeState: null,
                afterState: [
                    'name' => $command->name,
                    'permissions' => $command->permissionNames,
                ],
            );
        });
    }
}
