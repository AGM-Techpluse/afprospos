<?php

declare(strict_types=1);

namespace Domain\RBAC\Application\Queries;

use Domain\RBAC\Domain\Repositories\RoleRepository;
use Illuminate\Support\Str;

/**
 * Roles are real, admin-editable database rows (Spatie's Role/Permission
 * models, wrapped by RoleRepository) — not a fixed catalog. The *set of
 * assignable permissions* stays code-defined (config('afprospos.permissions'),
 * new permission types are added by developers building new modules), but
 * *which roles exist and what they're granted* is fully dynamic.
 */
final class AvailableRolesQuery
{
    public function __construct(private readonly RoleRepository $roles) {}

    /** @return array<int, array{name:string, modules: array<int, array{module:string, actions:string}>}> */
    public function all(): array
    {
        $allPermissionNames = $this->allPermissionNames();

        return collect($this->roles->all())
            ->map(fn (array $role): array => [
                'name' => $role['name'],
                'modules' => $this->expand($role['permissionNames'], $allPermissionNames),
            ])
            ->values()
            ->all();
    }

    /** @return array{name:string, permissionNames:string[]}|null */
    public function find(string $name): ?array
    {
        $role = $this->roles->find($name);

        return $role !== null ? ['name' => $role['name'], 'permissionNames' => $role['permissionNames']] : null;
    }

    /** @return string[] */
    public function names(): array
    {
        return $this->roles->names();
    }

    /** The fixed, code-defined catalog of assignable permissions, grouped by module. */
    /** @return array<int, array{module:string, actions: array<int, array{key:string, label:string}>}> */
    public function catalog(): array
    {
        return collect((array) config('afprospos.permissions', []))
            ->map(static fn (array $actions, string $module): array => [
                'module' => Str::headline($module),
                'actions' => collect($actions)
                    ->map(static fn (string $action): array => [
                        'key' => "{$module}.{$action}",
                        'label' => Str::headline($action),
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /** The fixed, code-defined list of every assignable permission name (e.g. "staff.view"). */
    /** @return string[] */
    public function assignablePermissionNames(): array
    {
        return $this->allPermissionNames();
    }

    /** @return array<int, array{module:string, actions:string}> */
    private function expand(array $permissionNames, array $allPermissionNames): array
    {
        if (count($permissionNames) > 0 && count(array_diff($allPermissionNames, $permissionNames)) === 0) {
            return [['module' => 'All modules', 'actions' => 'Full access']];
        }

        $catalog = (array) config('afprospos.permissions', []);
        $granted = array_flip($permissionNames);

        $modules = [];

        foreach ($catalog as $module => $actions) {
            $grantedActions = array_values(array_filter(
                (array) $actions,
                static fn (string $action): bool => isset($granted["{$module}.{$action}"]),
            ));

            if ($grantedActions === []) {
                continue;
            }

            $modules[] = [
                'module' => Str::headline($module),
                'actions' => count($grantedActions) === count($actions)
                    ? 'Full access'
                    : implode(', ', array_map(static fn (string $action): string => Str::headline($action), $grantedActions)),
            ];
        }

        return $modules;
    }

    /** @return string[] */
    private function allPermissionNames(): array
    {
        $names = [];

        foreach ((array) config('afprospos.permissions', []) as $module => $actions) {
            foreach ((array) $actions as $action) {
                $names[] = "{$module}.{$action}";
            }
        }

        return $names;
    }
}
