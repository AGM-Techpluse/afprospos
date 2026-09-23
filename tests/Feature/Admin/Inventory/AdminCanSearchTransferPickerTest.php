<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Inventory;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\ProductRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** INV-BR-19: the transfer picker searches by name/brand/SKU and filters by category, scoped to one shop's stock. */
class AdminCanSearchTransferPickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_searching_the_transfer_picker_filters_by_shop_and_category(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create();
        $otherShop = ShopRecord::factory()->create();

        $accessory = ProductRecord::factory()->create(['category' => 'Accessories', 'brand' => 'Anker']);
        $accessorySku = SkuRecord::factory()->nonSerialized()->create(['product_id' => $accessory->id]);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $accessorySku->id, 'shop_id' => $shop->id, 'on_hand' => 10, 'reserved' => 2]);

        $phone = ProductRecord::factory()->create(['category' => 'Phones', 'brand' => 'Apple']);
        $phoneSku = SkuRecord::factory()->nonSerialized()->create(['product_id' => $phone->id]);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $phoneSku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 0]);

        // Stock at a different shop should never show up for this from-shop search.
        InventoryStockLevelRecord::factory()->create(['sku_id' => $accessorySku->id, 'shop_id' => $otherShop->id, 'on_hand' => 20, 'reserved' => 0]);

        $response = $this->actingAs($owner, 'staff')->getJson(
            "/admin/inventory/transfers/search?from_shop_id={$shop->id}&category=Accessories",
        );

        $response->assertOk();
        $results = $response->json('results');

        $this->assertCount(1, $results);
        $this->assertSame($accessorySku->id, $results[0]['sku_id']);
        $this->assertSame(8, $results[0]['available']);
    }

    public function test_no_from_shop_returns_no_results(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $response = $this->actingAs($owner, 'staff')->getJson('/admin/inventory/transfers/search');

        $response->assertOk();
        $this->assertSame([], $response->json('results'));
    }
}
