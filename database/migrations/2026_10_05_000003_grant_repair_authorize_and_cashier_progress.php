<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Repair authorization gets its own permission (`repairs.authorize`), held by the
 * Shop Owner and Manager. Cashiers can run the rest of the repair flow, so they
 * gain `repairs.progress`. These are additive grants only: roles are admin-editable,
 * so nothing else an admin changed is touched.
 */
return new class extends Migration
{
    private const GUARD = 'staff';

    public function up(): void
    {
        $authorize = Permission::query()->firstOrCreate(['name' => 'repairs.authorize', 'guard_name' => self::GUARD]);
        Permission::query()->firstOrCreate(['name' => 'repairs.progress', 'guard_name' => self::GUARD]);

        foreach (['Shop Owner', 'Manager'] as $roleName) {
            Role::query()->where(['name' => $roleName, 'guard_name' => self::GUARD])->first()?->givePermissionTo($authorize);
        }

        Role::query()->where(['name' => 'Cashier', 'guard_name' => self::GUARD])->first()?->givePermissionTo('repairs.progress');
    }

    public function down(): void
    {
        foreach (['Shop Owner', 'Manager'] as $roleName) {
            Role::query()->where(['name' => $roleName, 'guard_name' => self::GUARD])->first()?->revokePermissionTo('repairs.authorize');
        }

        Role::query()->where(['name' => 'Cashier', 'guard_name' => self::GUARD])->first()?->revokePermissionTo('repairs.progress');

        Permission::query()->where(['name' => 'repairs.authorize', 'guard_name' => self::GUARD])->delete();
    }
};
