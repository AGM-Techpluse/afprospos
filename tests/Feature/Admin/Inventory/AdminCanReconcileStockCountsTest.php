<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Inventory;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanReconcileStockCountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_mixed_batch_applies_the_good_rows_and_reports_the_bad_one(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $short = InventoryStockLevelRecord::factory()->create(['on_hand' => 10, 'reserved' => 0]);
        // Physical count below what's currently reserved must fail without affecting the good row.
        $overReserved = InventoryStockLevelRecord::factory()->create(['on_hand' => 10, 'reserved' => 8]);

        $response = $this->actingAs($owner, 'staff')->post('/admin/inventory/reconciliation', [
            'counts' => [
                ['sku_id' => $short->sku_id, 'shop_id' => $short->shop_id, 'physical_count' => 6, 'reason_code' => 'lost', 'note' => null],
                ['sku_id' => $overReserved->sku_id, 'shop_id' => $overReserved->shop_id, 'physical_count' => 2, 'reason_code' => 'lost', 'note' => null],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('inventory_stock_levels', ['id' => $short->id, 'on_hand' => 6]);
        $this->assertDatabaseHas('inventory_stock_levels', ['id' => $overReserved->id, 'on_hand' => 10]);
    }

    public function test_a_matching_count_is_a_no_op(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $level = InventoryStockLevelRecord::factory()->create(['on_hand' => 10]);

        $response = $this->actingAs($owner, 'staff')->post('/admin/inventory/reconciliation', [
            'counts' => [
                ['sku_id' => $level->sku_id, 'shop_id' => $level->shop_id, 'physical_count' => 10, 'reason_code' => 'lost', 'note' => null],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('inventory_stock_levels', ['id' => $level->id, 'on_hand' => 10]);
    }

    public function test_rejects_an_invalid_reason_code(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $level = InventoryStockLevelRecord::factory()->create(['on_hand' => 10]);

        $response = $this->actingAs($owner, 'staff')->post('/admin/inventory/reconciliation', [
            'counts' => [
                ['sku_id' => $level->sku_id, 'shop_id' => $level->shop_id, 'physical_count' => 6, 'reason_code' => 'not-a-real-reason', 'note' => null],
            ],
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseHas('inventory_stock_levels', ['id' => $level->id, 'on_hand' => 10]);
    }

    public function test_blocks_a_staff_member_without_inventory_edit(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier'); // has inventory.view only, per config/afprospos.php

        $level = InventoryStockLevelRecord::factory()->create(['on_hand' => 10]);

        $response = $this->actingAs($cashier, 'staff')->post('/admin/inventory/reconciliation', [
            'counts' => [
                ['sku_id' => $level->sku_id, 'shop_id' => $level->shop_id, 'physical_count' => 6, 'reason_code' => 'lost', 'note' => null],
            ],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('inventory_stock_levels', ['id' => $level->id, 'on_hand' => 10]);
    }
}
