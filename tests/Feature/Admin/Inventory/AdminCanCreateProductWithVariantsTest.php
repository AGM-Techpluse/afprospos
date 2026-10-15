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

/** INV-BR-16/17/18: non-serialized products can have several variants, distinguished by attributes. */
class AdminCanCreateProductWithVariantsTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_one_product_with_several_variants_in_one_submission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'MNS']);

        $response = $this->actingAs($owner, 'staff')->post('/admin/inventory/products/with-variants', [
            'brand' => 'Generic',
            'model' => 'Tempered Glass Screen Guard',
            'category' => 'Accessories',
            'condition' => 'new',
            'shop_id' => $shop->id,
            'variants' => [
                [
                    'attributes' => ['brand' => 'Anker'],
                    'cost_price_minor' => 50000,
                    'markup_percent' => 20,
                    'initial_quantity' => 10,
                ],
                [
                    'attributes' => ['brand' => 'Belkin'],
                    'cost_price_minor' => 60000,
                    'markup_percent' => 20,
                    'initial_quantity' => 15,
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('inventory_products', 1);
        $this->assertDatabaseCount('inventory_skus', 2);
        $this->assertDatabaseHas('inventory_stock_levels', ['on_hand' => 10]);
        $this->assertDatabaseHas('inventory_stock_levels', ['on_hand' => 15]);
        $this->assertDatabaseHas('audit_logs', ['module' => 'Inventory', 'event_type' => 'ProductCreatedWithVariants']);
    }

    public function test_creates_serialized_variants_with_imeis_in_one_submission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'MNS']);

        $response = $this->actingAs($owner, 'staff')->post('/admin/inventory/products/with-variants', [
            'brand' => 'Apple',
            'model' => 'iPhone 15 Pro',
            'category' => 'Phones',
            'condition' => 'new',
            'is_serialized' => true,
            'shop_id' => $shop->id,
            'variants' => [
                [
                    'attributes' => ['color' => 'Natural Titanium', 'storage' => '256GB'],
                    'cost_price_minor' => 900_000_00,
                    'markup_percent' => 15,
                    'initial_imeis' => ['356938035601001'],
                ],
                [
                    'attributes' => ['color' => 'Blue Titanium', 'storage' => '512GB'],
                    'cost_price_minor' => 1_050_000_00,
                    'markup_percent' => 15,
                    'initial_imeis' => ['356938035601002'],
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('inventory_products', 1);
        $this->assertSame(2, SkuRecord::query()->where('is_serialized', true)->count());
        $this->assertSame(2, InventoryItemRecord::query()->count());
        $this->assertDatabaseHas('inventory_items', ['imei' => '356938035601001', 'current_shop_id' => $shop->id]);
        $this->assertDatabaseHas('inventory_items', ['imei' => '356938035601002', 'current_shop_id' => $shop->id]);
    }

    public function test_adding_a_variant_to_an_existing_product_does_not_touch_its_other_variants(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'MNS']);

        $this->actingAs($owner, 'staff')->post('/admin/inventory/products', [
            'brand' => 'Generic',
            'model' => 'Tempered Glass Screen Guard',
            'category' => 'Accessories',
            'condition' => 'new',
            'is_serialized' => false,
            'cost_price_minor' => 50000,
            'markup_percent' => 20,
            'shop_id' => $shop->id,
            'initial_quantity' => 10,
            'attributes' => ['brand' => 'Anker'],
        ]);

        $productId = ProductRecord::query()->firstOrFail()->id;

        $response = $this->actingAs($owner, 'staff')->post("/admin/inventory/products/{$productId}/variants", [
            'shop_id' => $shop->id,
            'is_serialized' => false,
            'cost_price_minor' => 60000,
            'markup_percent' => 25,
            'initial_quantity' => 8,
            'attributes' => ['brand' => 'Belkin'],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('inventory_skus', 2);
        $this->assertDatabaseHas('inventory_stock_levels', ['on_hand' => 10]);
        $this->assertDatabaseHas('inventory_stock_levels', ['on_hand' => 8]);
    }

    public function test_adding_a_serialized_variant_to_an_existing_product_creates_serialized_items(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'MNS']);

        $this->actingAs($owner, 'staff')->post('/admin/inventory/products', [
            'brand' => 'Apple',
            'model' => 'iPhone 15 Pro',
            'category' => 'Phones',
            'condition' => 'new',
            'is_serialized' => true,
            'cost_price_minor' => 900_000_00,
            'markup_percent' => 15,
            'shop_id' => $shop->id,
            'initial_imeis' => ['356938035601003'],
            'attributes' => ['color' => 'Natural Titanium', 'storage' => '256GB'],
        ])->assertRedirect();

        $productId = ProductRecord::query()->firstOrFail()->id;

        $response = $this->actingAs($owner, 'staff')->post("/admin/inventory/products/{$productId}/variants", [
            'shop_id' => $shop->id,
            'is_serialized' => true,
            'cost_price_minor' => 1_050_000_00,
            'markup_percent' => 15,
            'initial_imeis' => ['356938035601004'],
            'attributes' => ['color' => 'Blue Titanium', 'storage' => '512GB'],
        ]);

        $response->assertRedirect();
        $this->assertSame(2, SkuRecord::query()->where('is_serialized', true)->count());
        $this->assertDatabaseHas('inventory_items', ['imei' => '356938035601004', 'current_shop_id' => $shop->id]);
    }

    public function test_re_adding_the_same_variant_attributes_restocks_that_variant_instead_of_duplicating_it(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'MNS']);

        $payload = [
            'brand' => 'Generic',
            'model' => 'Tempered Glass Screen Guard',
            'category' => 'Accessories',
            'condition' => 'new',
            'is_serialized' => false,
            'cost_price_minor' => 50000,
            'markup_percent' => 20,
            'shop_id' => $shop->id,
            'initial_quantity' => 10,
            'attributes' => ['brand' => 'Anker'],
        ];

        $this->actingAs($owner, 'staff')->post('/admin/inventory/products', $payload)->assertRedirect();
        $this->actingAs($owner, 'staff')->post('/admin/inventory/products', $payload)->assertRedirect();

        $this->assertDatabaseCount('inventory_skus', 1);
        $this->assertDatabaseHas('inventory_stock_levels', ['shop_id' => $shop->id, 'on_hand' => 20]);
    }

    public function test_different_variant_attributes_for_the_same_product_create_distinct_skus_not_a_restock(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'MNS']);

        $base = [
            'brand' => 'Generic',
            'model' => 'Tempered Glass Screen Guard',
            'category' => 'Accessories',
            'condition' => 'new',
            'is_serialized' => false,
            'cost_price_minor' => 50000,
            'markup_percent' => 20,
            'shop_id' => $shop->id,
            'initial_quantity' => 10,
        ];

        $this->actingAs($owner, 'staff')->post('/admin/inventory/products', [...$base, 'attributes' => ['brand' => 'Anker']])->assertRedirect();
        $this->actingAs($owner, 'staff')->post('/admin/inventory/products', [...$base, 'attributes' => ['brand' => 'Belkin']])->assertRedirect();

        $this->assertDatabaseCount('inventory_products', 1);
        $this->assertDatabaseCount('inventory_skus', 2);
    }
}
