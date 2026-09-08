<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\RBAC;

use Database\Seeders\RolePermissionSeeder;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanAssignRoleToStaffTest extends TestCase
{
    use RefreshDatabase;

    public function test_lets_an_admin_with_staff_assign_assign_a_role_to_another_staff_member(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $target = StaffRecord::factory()->create();

        $response = $this->actingAs($owner, 'staff')
            ->post("/admin/staff/{$target->id}/roles", ['role_name' => 'Technician']);

        $response->assertRedirect();
        $this->assertTrue($target->fresh()->hasRole('Technician'));

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'RBAC',
            'event_type' => 'RoleAssignedToStaff',
            'subject_type' => 'staff',
            'subject_id' => $target->id,
        ]);
    }

    public function test_blocks_a_staff_member_without_staff_assign_from_assigning_a_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier'); // has no staff.* permissions per config/afprospos.php

        $target = StaffRecord::factory()->create();

        $response = $this->actingAs($cashier, 'staff')
            ->post("/admin/staff/{$target->id}/roles", ['role_name' => 'Technician']);

        $response->assertForbidden();
        $this->assertFalse($target->fresh()->hasRole('Technician'));
    }
}
