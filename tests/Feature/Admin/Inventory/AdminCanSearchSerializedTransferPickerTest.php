<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Inventory;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\ProductRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression: the bulk-transfer picker only ever searched non-serialized
 * stock levels (correctly — a serialized item has no quantity to move in
 * bulk), but the "Single serialized unit (IMEI)" tab was a raw
 * SKU-ID/inventory-item-ID number entry form with no way to find either
 * ID — a serialized product (e.g. a console, a phone) was effectively
 * untransferable through the UI. This covers the new search-serialized
 * endpoint that fixes that.
 */
class AdminCanSearchSerializedTransferPickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_searching_finds_an_available_serialized_unit_at_the_from_shop(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create();
        $otherShop = ShopRecord::factory()->create();

        $console = ProductRecord::factory()->create(['category' => 'Consoles', 'brand' => 'Sony', 'model' => 'PlayStation 5']);
        $sku = SkuRecord::factory()->create(['product_id' => $console->id, 'sku_code' => 'MAIN-CON-000001']);
        $unit = InventoryItemRecord::factory()->create(['sku_id' => $sku->id, 'current_shop_id' => $shop->id, 'imei' => '990000000000935']);

        // A reserved unit at the same shop is not transferable and should not show up.
        InventoryItemRecord::factory()->reserved()->create(['sku_id' => $sku->id, 'current_shop_id' => $shop->id]);
        // An available unit of the same SKU at a different shop should never show up for this from-shop search.
        InventoryItemRecord::factory()->create(['sku_id' => $sku->id, 'current_shop_id' => $otherShop->id]);

        $response = $this->actingAs($owner, 'staff')->getJson(
            "/admin/inventory/transfers/search-serialized?from_shop_id={$shop->id}&search=sony",
        );

        $response->assertOk();
        $results = $response->json('results');

        $this->assertCount(1, $results);
        $this->assertSame($unit->id, $results[0]['id']);
        $this->assertSame('990000000000935', $results[0]['imei']);
        $this->assertSame('MAIN-CON-000001', $results[0]['sku_code']);
    }

    public function test_no_from_shop_returns_no_results(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $response = $this->actingAs($owner, 'staff')->getJson('/admin/inventory/transfers/search-serialized');

        $response->assertOk();
        $this->assertSame([], $response->json('results'));
    }

    public function test_the_found_unit_can_then_be_transferred(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $fromShop = ShopRecord::factory()->create();
        $toShop = ShopRecord::factory()->create();

        $sku = SkuRecord::factory()->create();
        $unit = InventoryItemRecord::factory()->create(['sku_id' => $sku->id, 'current_shop_id' => $fromShop->id]);

        $search = $this->actingAs($owner, 'staff')->getJson(
            "/admin/inventory/transfers/search-serialized?from_shop_id={$fromShop->id}",
        );
        $found = $search->json('results.0');
        $this->assertSame($unit->id, $found['id']);

        $transferResponse = $this->actingAs($owner, 'staff')->post('/admin/inventory/transfers', [
            'sku_id' => $found['sku_id'],
            'inventory_item_id' => $found['id'],
            'from_shop_id' => $fromShop->id,
            'to_shop_id' => $toShop->id,
        ]);

        $transferResponse->assertRedirect();
        $this->assertDatabaseHas('inventory_items', ['id' => $unit->id, 'status' => 'transferring']);
        $this->assertDatabaseHas('inventory_transfers', ['inventory_item_id' => $unit->id, 'from_shop_id' => $fromShop->id, 'to_shop_id' => $toShop->id]);
    }
}
