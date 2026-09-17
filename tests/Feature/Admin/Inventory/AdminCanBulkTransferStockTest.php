<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Inventory;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanBulkTransferStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_lets_an_admin_bulk_transfer_non_serialized_stock_to_one_destination_shop(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $toShop = ShopRecord::factory()->create();
        $level = InventoryStockLevelRecord::factory()->create(['on_hand' => 10]);

        $response = $this->actingAs($owner, 'staff')->post('/admin/inventory/stock/bulk-transfer', [
            'to_shop_id' => $toShop->id,
            'items' => [
                ['sku_id' => $level->sku_id, 'from_shop_id' => $level->shop_id, 'quantity' => 6],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('inventory_stock_levels', ['id' => $level->id, 'on_hand' => 4]);
        $this->assertDatabaseHas('inventory_transfers', [
            'sku_id' => $level->sku_id,
            'from_shop_id' => $level->shop_id,
            'to_shop_id' => $toShop->id,
            'quantity' => 6,
        ]);
    }

    public function test_a_row_requesting_more_than_available_fails_without_affecting_others(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $toShop = ShopRecord::factory()->create();
        $good = InventoryStockLevelRecord::factory()->create(['on_hand' => 10]);
        $bad = InventoryStockLevelRecord::factory()->create(['on_hand' => 3]);

        $response = $this->actingAs($owner, 'staff')->post('/admin/inventory/stock/bulk-transfer', [
            'to_shop_id' => $toShop->id,
            'items' => [
                ['sku_id' => $good->sku_id, 'from_shop_id' => $good->shop_id, 'quantity' => 5],
                ['sku_id' => $bad->sku_id, 'from_shop_id' => $bad->shop_id, 'quantity' => 50],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('inventory_stock_levels', ['id' => $good->id, 'on_hand' => 5]);
        $this->assertDatabaseHas('inventory_stock_levels', ['id' => $bad->id, 'on_hand' => 3]);
        $this->assertDatabaseMissing('inventory_transfers', ['sku_id' => $bad->sku_id]);
    }
}
