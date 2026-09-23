<?php

declare(strict_types=1);

namespace Domain\Identity\Infrastructure\Laravel;

use Domain\Identity\Application\Contracts\StaffLookup;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Spatie\Permission\Models\Permission;

/** The Contract's implementation — Eloquent is permitted here (Infrastructure query layer, CPNC §2.1). */
final class EloquentStaffLookup implements StaffLookup
{
    public function find(int $id): ?array
    {
        $staff = StaffRecord::query()->find($id);

        return $staff !== null ? ['id' => $staff->id, 'name' => $staff->name, 'email' => $staff->email] : null;
    }

    public function findByRole(string $role): array
    {
        return StaffRecord::query()
            ->where('status', 'active')
            ->whereHas('roles', function ($query) use ($role): void {
                $query->where('name', $role);
            })
            ->get()
            ->map(static fn (StaffRecord $staff): array => ['id' => $staff->id, 'name' => $staff->name, 'email' => $staff->email])
            ->all();
    }

    public function findByPermission(string $permission): array
    {
        // Spatie's `permission()` scope throws PermissionDoesNotExist rather
        // than returning empty when the name isn't seeded yet (a real state
        // pre-seed, or in tests that never seed RBAC data at all) — nobody
        // can hold a permission that doesn't exist, so treat that as "no
        // recipients" rather than an error.
        if (! Permission::query()->where('name', $permission)->where('guard_name', 'staff')->exists()) {
            return [];
        }

        return StaffRecord::query()
            ->where('status', 'active')
            ->permission($permission)
            ->get()
            ->map(static fn (StaffRecord $staff): array => ['id' => $staff->id, 'name' => $staff->name, 'email' => $staff->email])
            ->all();
    }
}
