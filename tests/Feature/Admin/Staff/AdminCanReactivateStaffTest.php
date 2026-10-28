<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Staff;

use Database\Seeders\RolePermissionSeeder;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanReactivateStaffTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_reactivate_a_deactivated_staff_member(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $target = StaffRecord::factory()->deactivated()->create();

        $response = $this->actingAs($owner, 'staff')->post("/admin/staff/{$target->id}/reactivate");

        $response->assertRedirect();
        $this->assertSame('active', $target->fresh()->status);
    }

    public function test_a_cashier_cannot_reactivate_staff(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');

        $target = StaffRecord::factory()->deactivated()->create();

        $response = $this->actingAs($cashier, 'staff')->post("/admin/staff/{$target->id}/reactivate");

        $response->assertForbidden();
        $this->assertSame('deactivated', $target->fresh()->status);
    }
}
