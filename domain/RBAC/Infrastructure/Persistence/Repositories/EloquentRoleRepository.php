<?php

declare(strict_types=1);

namespace Domain\RBAC\Infrastructure\Persistence\Repositories;

use Domain\RBAC\Domain\Repositories\RoleRepository;
use Spatie\Permission\Models\Role;

final class EloquentRoleRepository implements RoleRepository
{
    private const GUARD = 'staff';

    public function existsWithName(string $name): bool
    {
        return Role::query()
            ->where('name', $name)
            ->where('guard_name', self::GUARD)
            ->exists();
    }

    public function all(): array
    {
        return Role::query()
            ->where('guard_name', self::GUARD)
            ->with('permissions')
            ->orderBy('name')
            ->get()
            ->map(static fn (Role $role): array => self::toArray($role))
            ->all();
    }

    public function find(string $name): ?array
    {
        $role = Role::query()
            ->where('name', $name)
            ->where('guard_name', self::GUARD)
            ->with('permissions')
            ->first();

        return $role !== null ? self::toArray($role) : null;
    }

    public function names(): array
    {
        return Role::query()
            ->where('guard_name', self::GUARD)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    public function create(string $name, array $permissionNames): int
    {
        $role = Role::query()->create([
            'name' => $name,
            'guard_name' => self::GUARD,
        ]);

        $role->syncPermissions($permissionNames);

        return $role->id;
    }

    public function updatePermissions(string $name, array $permissionNames): void
    {
        $role = Role::query()
            ->where('name', $name)
            ->where('guard_name', self::GUARD)
            ->firstOrFail();

        $role->syncPermissions($permissionNames);
    }

    /** @return array{id:int, name:string, permissionNames:string[]} */
    private static function toArray(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'permissionNames' => $role->permissions->pluck('name')->all(),
        ];
    }
}
