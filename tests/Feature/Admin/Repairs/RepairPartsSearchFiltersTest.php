<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Repairs;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\ProductRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Both of these part-search endpoints (reserving a real part during a repair,
 * and the device catalog's "suggested parts" picker) must find actual repair
 * parts and never offer a sellable-only product — the opposite rule from
 * Sales/Checkout's own search (CheckoutSearchFiltersTest), which must never
 * offer a repair-only part. Before the purposes filter existed, both of these
 * reused Checkout's sale-only filter, so a dedicated repair_part product was
 * invisible here and a plain phone could confusingly surface as a "part".
 */
class RepairPartsSearchFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_repair_job_part_search_finds_repair_parts_and_excludes_sellable_only_products(): void
    {
        $owner = $this->owner();
        $shop = ShopRecord::factory()->create();
        $repair = RepairJobRecord::factory()->create(['shop_id' => $shop->id]);

        $screen = $this->product($shop, ['brand' => 'Apple', 'model' => 'iPhone 15 Pro Screen', 'category' => 'Parts', 'purpose' => 'repair_part']);
        $phone = $this->product($shop, ['brand' => 'Apple', 'model' => 'iPhone 15 Pro', 'category' => 'Phones', 'purpose' => 'sale']);

        $results = $this->actingAs($owner, 'staff')
            ->getJson("/admin/repairs/{$repair->id}/parts/search?q=iPhone 15 Pro")
            ->assertOk()
            ->json('results');

        $ids = array_column($results, 'sku_id');
        $this->assertContains($screen->id, $ids);
        $this->assertNotContains($phone->id, $ids);
    }

    public function test_suggested_parts_search_finds_repair_parts_and_excludes_sellable_only_products(): void
    {
        $owner = $this->owner();
        $shop = ShopRecord::factory()->create();

        $screen = $this->product($shop, ['brand' => 'Apple', 'model' => 'iPhone 15 Pro Screen', 'category' => 'Parts', 'purpose' => 'repair_part']);
        $phone = $this->product($shop, ['brand' => 'Apple', 'model' => 'iPhone 15 Pro', 'category' => 'Phones', 'purpose' => 'sale']);

        $results = $this->actingAs($owner, 'staff')
            ->getJson('/admin/settings/device-catalog/parts/search?q=iPhone 15 Pro')
            ->assertOk()
            ->json('results');

        $ids = array_column($results, 'sku_id');
        $this->assertContains($screen->id, $ids);
        $this->assertNotContains($phone->id, $ids);
    }

    private function owner(): StaffRecord
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        return $owner;
    }

    /** @param  array<string, mixed>  $product */
    private function product(ShopRecord $shop, array $product): SkuRecord
    {
        $productRecord = ProductRecord::query()->create($product);
        $sku = SkuRecord::factory()->create(['product_id' => $productRecord->id, 'is_serialized' => false]);

        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 0]);

        return $sku;
    }
}
