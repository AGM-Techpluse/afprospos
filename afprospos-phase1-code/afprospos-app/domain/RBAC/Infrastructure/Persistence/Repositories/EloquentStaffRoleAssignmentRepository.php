<?php

declare(strict_types=1);

namespace Domain\RBAC\Infrastructure\Persistence\Repositories;

use Domain\RBAC\Domain\Repositories\StaffRoleAssignmentRepository;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Spatie\Permission\Models\Role;

final class EloquentStaffRoleAssignmentRepository implements StaffRoleAssignmentRepository
{
    public function roleExists(string $roleName): bool
    {
        return Role::query()
            ->where('name', $roleName)
            ->where('guard_name', 'staff')
            ->exists();
    }

    public function currentRoleNames(StaffId $staffId): array
    {
        return StaffRecord::query()
            ->findOrFail($staffId->value)
            ->getRoleNames()
            ->all();
    }

    public function assignRole(StaffId $staffId, string $roleName): void
    {
        StaffRecord::query()->findOrFail($staffId->value)->assignRole($roleName);
    }

    public function revokeRole(StaffId $staffId, string $roleName): void
    {
        StaffRecord::query()->findOrFail($staffId->value)->removeRole($roleName);
    }
}
