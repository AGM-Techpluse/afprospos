<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Roles;

use Database\Seeders\RolePermissionSeeder;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCanUpdateRolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_lets_an_admin_with_staff_assign_update_a_roles_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $response = $this->actingAs($owner, 'staff')->put('/admin/staff/roles/'.urlencode('Technician'), [
            'permissions' => ['repairs.view', 'reports.view'],
        ]);

        $response->assertRedirect('/admin/staff/roles');

        $role = Role::query()->where('name', 'Technician')->where('guard_name', 'staff')->firstOrFail();
        $this->assertEqualsCanonicalizing(['repairs.view', 'reports.view'], $role->permissions->pluck('name')->all());

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'RBAC',
            'event_type' => 'RolePermissionsUpdated',
            'subject_type' => 'role',
            'subject_id' => $role->id,
        ]);
    }

    public function test_blocks_a_staff_member_without_staff_assign_from_updating_role_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier'); // has no staff.assign permission per config/afprospos.php

        $before = Role::query()->where('name', 'Technician')->where('guard_name', 'staff')->firstOrFail();
        $beforePermissions = $before->permissions->pluck('name')->all();

        $response = $this->actingAs($cashier, 'staff')->put('/admin/staff/roles/'.urlencode('Technician'), [
            'permissions' => [],
        ]);

        $response->assertForbidden();

        $after = Role::query()->where('name', 'Technician')->where('guard_name', 'staff')->firstOrFail();
        $this->assertEqualsCanonicalizing($beforePermissions, $after->permissions->pluck('name')->all());
    }
}
