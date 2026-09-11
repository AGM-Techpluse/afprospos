<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Inventory;

use Database\Seeders\RolePermissionSeeder;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanCreateProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_lets_an_admin_with_inventory_create_add_a_non_serialized_product_with_initial_stock(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'MNS']);

        $response = $this->actingAs($owner, 'staff')->post('/admin/inventory/products', [
            'brand' => 'Anker',
            'model' => 'PowerCore 10000',
            'category' => 'Accessories',
            'condition' => 'new',
            'is_serialized' => false,
            'cost_price_minor' => 15_000_00,
            'markup_percent' => 20,
            'shop_id' => $shop->id,
            'initial_quantity' => 25,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('inventory_products', ['brand' => 'Anker', 'model' => 'PowerCore 10000']);
        $this->assertDatabaseHas('inventory_skus', ['selling_price_minor' => 18_000_00]);
        $this->assertDatabaseHas('inventory_stock_levels', ['shop_id' => $shop->id, 'on_hand' => 25, 'reserved' => 0]);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'Inventory',
            'event_type' => 'ProductCreated',
        ]);
    }

    public function test_lets_an_admin_add_a_serialized_product_with_an_initial_imei(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'MNS']);

        $response = $this->actingAs($owner, 'staff')->post('/admin/inventory/products', [
            'brand' => 'Apple',
            'model' => 'iPhone 15',
            'category' => 'Phones',
            'condition' => 'new',
            'is_serialized' => true,
            'cost_price_minor' => 400_000_00,
            'markup_percent' => 15,
            'shop_id' => $shop->id,
            'initial_imeis' => ['123456789012345'],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('inventory_items', [
            'imei' => '123456789012345',
            'current_shop_id' => $shop->id,
            'status' => 'available',
        ]);
    }

    public function test_blocks_a_staff_member_without_inventory_create_from_adding_a_product(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier'); // has no inventory.create permission per config/afprospos.php
        $shop = ShopRecord::factory()->create();

        $response = $this->actingAs($cashier, 'staff')->post('/admin/inventory/products', [
            'brand' => 'Unauthorized',
            'model' => 'Device',
            'category' => 'Phones',
            'condition' => 'new',
            'is_serialized' => false,
            'cost_price_minor' => 1000,
            'markup_percent' => 10,
            'shop_id' => $shop->id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('inventory_products', ['brand' => 'Unauthorized']);
    }
}
