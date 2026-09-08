<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeds the RBAC permission catalog (config/afprospos.php) and the
 * starting role -> permission configuration transcribed from BRD §5's
 * RBAC Permission Matrix. Idempotent (firstOrCreate throughout) so it is
 * safe to re-run in any environment.
 */
final class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissionCatalog = (array) config('afprospos.permissions');
        $guard = 'staff';

        $allPermissionNames = [];

        foreach ($permissionCatalog as $module => $actions) {
            foreach ($actions as $action) {
                $name = "{$module}.{$action}";
                $allPermissionNames[] = $name;

                Permission::query()->firstOrCreate([
                    'name' => $name,
                    'guard_name' => $guard,
                ]);
            }
        }

        $roleStartingPermissions = (array) config('afprospos.role_starting_permissions');

        foreach ($roleStartingPermissions as $roleName => $moduleGrants) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => $guard,
            ]);

            if (isset($moduleGrants['*']) && $moduleGrants['*'] === 'full') {
                $role->syncPermissions($allPermissionNames);

                continue;
            }

            $grantedPermissionNames = [];

            foreach ($moduleGrants as $module => $grant) {
                if ($grant === 'full') {
                    $grantedPermissionNames = [
                        ...$grantedPermissionNames,
                        ...array_map(
                            static fn (string $action): string => "{$module}.{$action}",
                            (array) ($permissionCatalog[$module] ?? []),
                        ),
                    ];

                    continue;
                }

                foreach ((array) $grant as $action) {
                    $grantedPermissionNames[] = "{$module}.{$action}";
                }
            }

            $role->syncPermissions($grantedPermissionNames);
        }
    }
}
