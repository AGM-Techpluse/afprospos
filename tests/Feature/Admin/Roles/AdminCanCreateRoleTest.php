<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Roles;

use Database\Seeders\RolePermissionSeeder;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCanCreateRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_lets_an_admin_with_staff_assign_create_a_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $response = $this->actingAs($owner, 'staff')->post('/admin/staff/roles', [
            'name' => 'Inventory Auditor',
            'permissions' => ['inventory.view', 'reports.view'],
        ]);

        $response->assertRedirect('/admin/staff/roles');

        $role = Role::query()->where('name', 'Inventory Auditor')->where('guard_name', 'staff')->first();
        $this->assertNotNull($role);
        $this->assertEqualsCanonicalizing(['inventory.view', 'reports.view'], $role->permissions->pluck('name')->all());

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'RBAC',
            'event_type' => 'RoleCreated',
            'subject_type' => 'role',
            'subject_id' => $role->id,
        ]);
    }

    public function test_rejects_a_role_name_already_in_use(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $response = $this->actingAs($owner, 'staff')->post('/admin/staff/roles', [
            'name' => 'Cashier',
            'permissions' => ['sales.view'],
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame(
            1,
            Role::query()->where('name', 'Cashier')->where('guard_name', 'staff')->count(),
        );
    }

    public function test_blocks_a_staff_member_without_staff_assign_from_creating_a_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier'); // has no staff.assign permission per config/afprospos.php

        $response = $this->actingAs($cashier, 'staff')->post('/admin/staff/roles', [
            'name' => 'Unauthorized Role',
            'permissions' => [],
        ]);

        $response->assertForbidden();
        $this->assertSame(
            0,
            Role::query()->where('name', 'Unauthorized Role')->count(),
        );
    }
}
