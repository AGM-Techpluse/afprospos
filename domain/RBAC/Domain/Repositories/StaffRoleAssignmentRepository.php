<?php

declare(strict_types=1);

namespace Domain\RBAC\Domain\Repositories;

use Domain\Shared\Domain\ValueObjects\StaffId;

/**
 * Wraps Spatie's role pivot for a single staff member so the Application
 * layer never touches `StaffRecord`/`Spatie\Permission\Models\Role`
 * directly (CPNC §2.1 write pipeline: Handler -> Repository, not
 * Handler -> Eloquent).
 */
interface StaffRoleAssignmentRepository
{
    public function roleExists(string $roleName): bool;

    /** @return string[] */
    public function currentRoleNames(StaffId $staffId): array;

    public function assignRole(StaffId $staffId, string $roleName): void;

    public function revokeRole(StaffId $staffId, string $roleName): void;
}
