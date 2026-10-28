<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Staff;

use Database\Seeders\RolePermissionSeeder;
use Domain\RBAC\Infrastructure\Persistence\Eloquent\StaffShopGrantRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanDeleteStaffTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_delete_a_staff_member_with_no_existing_records(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $target = StaffRecord::factory()->deactivated()->create();
        $target->assignRole('Cashier');

        $response = $this->actingAs($owner, 'staff')->delete("/admin/staff/{$target->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('staff', ['id' => $target->id]);
        $this->assertDatabaseMissing('model_has_roles', ['model_id' => $target->id, 'model_type' => StaffRecord::class]);
    }

    public function test_deleting_a_staff_member_with_existing_shop_grant_history_fails(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $shop = ShopRecord::factory()->create();
        $target = StaffRecord::factory()->deactivated()->create();
        StaffShopGrantRecord::query()->create([
            'staff_id' => $target->id,
            'shop_id' => $shop->id,
            'granted_at' => now(),
        ]);

        $response = $this->actingAs($owner, 'staff')
            ->from("/admin/staff/{$target->id}")
            ->delete("/admin/staff/{$target->id}");

        $response->assertSessionHasErrors('staff');
        $this->assertDatabaseHas('staff', ['id' => $target->id]);
    }

    public function test_a_cashier_cannot_delete_staff(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');

        $target = StaffRecord::factory()->deactivated()->create();

        $response = $this->actingAs($cashier, 'staff')->delete("/admin/staff/{$target->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('staff', ['id' => $target->id]);
    }
}
