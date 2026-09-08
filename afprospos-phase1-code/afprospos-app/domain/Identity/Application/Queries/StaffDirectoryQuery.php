<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Queries;

use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;

/**
 * Read-side query backing the Admin staff list screen (Implementation
 * Plan Phase 1, item 5). Joins in role names for display convenience —
 * still a read-only Infrastructure-query-layer concern, not a write path,
 * so touching Spatie's relations here is permitted (CPNC §2.1/§2.4).
 */
final class StaffDirectoryQuery
{
    /** @return array<int, array{id:int, name:string, email:string, phone:string, status:string, roles:string[]}> */
    public function all(): array
    {
        return StaffRecord::query()
            ->with('roles')
            ->orderBy('name')
            ->get()
            ->map(static fn (StaffRecord $staff): array => [
                'id' => $staff->id,
                'name' => $staff->name,
                'email' => $staff->email,
                'phone' => $staff->phone,
                'status' => $staff->status,
                'roles' => $staff->getRoleNames()->all(),
            ])
            ->all();
    }
}
