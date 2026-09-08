<?php

declare(strict_types=1);

namespace Domain\RBAC\Application\Queries;

use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;

/**
 * Read-side query object — permitted to touch Eloquent directly
 * (CPNC §2.1/§2.4: reads bypass the Aggregate/Repository `save()` path
 * and query the Infrastructure query layer directly). Spatie's
 * `getAllPermissions()` already implements the BLD RBAC-BR-02 union
 * rule (permissions from every assigned role, de-duplicated) without any
 * bespoke merging logic here.
 */
final class EffectivePermissionsQuery
{
    /** @return string[] */
    public function forStaff(int $staffId): array
    {
        return StaffRecord::query()
            ->findOrFail($staffId)
            ->getAllPermissions()
            ->pluck('name')
            ->all();
    }

    public function staffHasRole(int $staffId, string $roleName): bool
    {
        return StaffRecord::query()
            ->findOrFail($staffId)
            ->hasRole($roleName);
    }
}
