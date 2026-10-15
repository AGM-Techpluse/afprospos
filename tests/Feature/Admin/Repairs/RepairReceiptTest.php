<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Repairs;

use Database\Seeders\RolePermissionSeeder;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepairReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_fully_paid_repair_renders_a_printable_receipt(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create();
        $repair = RepairJobRecord::factory()->create([
            'shop_id' => $shop->id,
            'repair_status' => 'completed',
            'financial_status' => 'fully_paid',
            'repair_cost_minor' => 500000,
        ]);

        $response = $this->actingAs($owner, 'staff')->get("/admin/repairs/{$repair->id}/receipt");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Repairs/Receipt')
            ->where('repair.id', $repair->id)
            ->where('repair.financial_status', 'fully_paid')
            ->where('repair.repair_cost_minor', 500000));
    }

    public function test_staff_without_shop_access_cannot_view_the_receipt(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $otherShop = ShopRecord::factory()->create();
        $staff = StaffRecord::factory()->create();
        $staff->assignRole('Cashier');
        $this->grantShopAccess($staff, $otherShop->id);

        $shop = ShopRecord::factory()->create();
        $repair = RepairJobRecord::factory()->create(['shop_id' => $shop->id]);

        $response = $this->actingAs($staff, 'staff')->get("/admin/repairs/{$repair->id}/receipt");

        $response->assertForbidden();
    }
}
