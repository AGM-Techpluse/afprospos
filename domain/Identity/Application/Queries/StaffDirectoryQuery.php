<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Queries;

use Domain\RBAC\Infrastructure\Persistence\Eloquent\StaffShopGrantRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;

/**
 * Read-side query backing the Admin staff list screen (Implementation
 * Plan Phase 1, item 5). Joins in role names for display convenience —
 * still a read-only Infrastructure-query-layer concern, not a write path,
 * so touching Spatie's relations here is permitted (CPNC §2.1/§2.4).
 */
final class StaffDirectoryQuery
{
    /**
     * @return array{data: array<int, array{id:int, name:string, email:string, phone:string, status:string, roles:string[]}>, current_page:int, last_page:int, per_page:int, total:int}
     */
    public function paginate(?string $search, ?string $status, ?string $role, int $page, int $perPage = 20, ?int $shopId = null): array
    {
        $query = StaffRecord::query()->with('roles')->orderBy('name');

        if ($search !== null && $search !== '') {
            $query->where(function ($inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        if ($role !== null && $role !== '') {
            $query->whereHas('roles', function ($inner) use ($role): void {
                $inner->where('name', $role);
            });
        }

        if ($shopId !== null) {
            $grantedStaffIds = StaffShopGrantRecord::query()
                ->where('shop_id', $shopId)
                ->whereNull('revoked_at')
                ->pluck('staff_id');

            $query->where(function ($inner) use ($grantedStaffIds): void {
                $inner->whereIn('id', $grantedStaffIds)
                    ->orWhereHas('roles', function ($roleQuery): void {
                        $roleQuery->where('name', config('afprospos.owner_role_name'));
                    });
            });
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->getCollection()->map(fn (StaffRecord $staff): array => $this->toArray($staff))->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    /** @return array{id:int, name:string, email:string, phone:string, status:string, roles:string[]}|null */
    public function find(int $id): ?array
    {
        $staff = StaffRecord::query()->with('roles')->find($id);

        return $staff !== null ? $this->toArray($staff) : null;
    }

    /** @return array{id:int, name:string, email:string, phone:string, status:string, roles:string[]} */
    private function toArray(StaffRecord $staff): array
    {
        return [
            'id' => $staff->id,
            'name' => $staff->name,
            'email' => $staff->email,
            'phone' => $staff->phone,
            'status' => $staff->status,
            'roles' => $staff->getRoleNames()->all(),
        ];
    }
}
