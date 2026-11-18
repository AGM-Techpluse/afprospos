<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Inventory;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryMasterEntryRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\ProductRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminCanUpdateProductMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_details_shared_by_variants_can_be_corrected(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        InventoryMasterEntryRecord::query()->firstOrCreate(['kind' => 'category', 'name' => 'Tablets']);
        InventoryMasterEntryRecord::query()->firstOrCreate(['kind' => 'unit', 'name' => 'box']);
        $product = ProductRecord::factory()->create([
            'brand' => 'Aple',
            'model' => 'iPhon 15',
            'category' => 'Phones',
            'unit' => 'pcs',
        ]);
        $sku = SkuRecord::factory()->create(['product_id' => $product->id]);
        SkuRecord::factory()->create(['product_id' => $product->id]);

        $this->actingAs($owner, 'staff')
            ->put("/admin/inventory/products/{$sku->id}/metadata", [
                'brand' => 'Apple',
                'model' => 'iPad Pro',
                'category' => 'Tablets',
                'unit' => 'box',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('inventory_products', [
            'id' => $product->id,
            'brand' => 'Apple',
            'model' => 'iPad Pro',
            'category' => 'Tablets',
            'unit' => 'box',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'ProductMetadataUpdated',
            'subject_type' => 'product',
            'subject_id' => $product->id,
        ]);
    }

    public function test_product_details_require_saved_category_and_unit_values(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $product = ProductRecord::factory()->create();
        $sku = SkuRecord::factory()->create(['product_id' => $product->id]);

        $this->actingAs($owner, 'staff')
            ->put("/admin/inventory/products/{$sku->id}/metadata", [
                'brand' => 'Apple',
                'model' => 'iPhone 15',
                'category' => 'Not configured',
                'unit' => 'not configured',
            ])
            ->assertSessionHasErrors(['category', 'unit']);
    }
}
