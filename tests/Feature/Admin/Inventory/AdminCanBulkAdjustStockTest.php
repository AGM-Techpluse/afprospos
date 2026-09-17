<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Inventory;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanBulkAdjustStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_mixed_batch_applies_the_good_rows_and_reports_the_bad_one(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $good = InventoryStockLevelRecord::factory()->create(['on_hand' => 10]);
        $bad = InventoryStockLevelRecord::factory()->create(['on_hand' => 5]);

        $response = $this->actingAs($owner, 'staff')->post('/admin/inventory/stock/bulk-adjust', [
            'reason' => 'Weekly stock count',
            'items' => [
                ['sku_id' => $good->sku_id, 'shop_id' => $good->shop_id, 'delta' => 5],
                // -100 would take on_hand negative — this row must fail without affecting the good one.
                ['sku_id' => $bad->sku_id, 'shop_id' => $bad->shop_id, 'delta' => -100],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('inventory_stock_levels', ['id' => $good->id, 'on_hand' => 15]);
        $this->assertDatabaseHas('inventory_stock_levels', ['id' => $bad->id, 'on_hand' => 5]);
    }

    public function test_bulk_adjust_supports_reducing_stock(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $level = InventoryStockLevelRecord::factory()->create(['on_hand' => 10]);

        $response = $this->actingAs($owner, 'staff')->post('/admin/inventory/stock/bulk-adjust', [
            'reason' => 'Damaged stock write-off',
            'items' => [
                ['sku_id' => $level->sku_id, 'shop_id' => $level->shop_id, 'delta' => -4],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('inventory_stock_levels', ['id' => $level->id, 'on_hand' => 6]);
    }
}
