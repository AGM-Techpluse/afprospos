<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Staff;

use Database\Seeders\RolePermissionSeeder;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanUpdateStaffProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_lets_an_admin_with_staff_edit_update_a_staff_members_profile(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $target = StaffRecord::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.test',
        ]);

        $response = $this->actingAs($owner, 'staff')->put("/admin/staff/{$target->id}", [
            'name' => 'Updated Name',
            'phone' => '08022222222',
            'email' => 'updated@example.test',
        ]);

        $response->assertRedirect("/admin/staff/{$target->id}");
        $this->assertDatabaseHas('staff', [
            'id' => $target->id,
            'name' => 'Updated Name',
            'phone' => '08022222222',
            'email' => 'updated@example.test',
        ]);
    }

    public function test_rejects_an_email_already_used_by_another_staff_member(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        StaffRecord::factory()->create(['email' => 'taken@example.test']);
        $target = StaffRecord::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.test',
        ]);

        $response = $this->actingAs($owner, 'staff')->put("/admin/staff/{$target->id}", [
            'name' => 'Updated Name',
            'phone' => $target->phone,
            'email' => 'taken@example.test',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseHas('staff', [
            'id' => $target->id,
            'name' => 'Original Name',
            'email' => 'original@example.test',
        ]);
    }

    public function test_blocks_a_staff_member_without_staff_edit_from_updating_a_profile(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier'); // has no staff.* permissions per config/afprospos.php

        $target = StaffRecord::factory()->create(['name' => 'Original Name']);

        $response = $this->actingAs($cashier, 'staff')->put("/admin/staff/{$target->id}", [
            'name' => 'Hacked Name',
            'phone' => $target->phone,
            'email' => $target->email,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('staff', [
            'id' => $target->id,
            'name' => 'Original Name',
        ]);
    }
}
