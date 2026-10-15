<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Inventory;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryMasterEntryRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\ProductRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Categories, base units and suppliers are admin-managed lists that products must be filed under. */
class AdminManagesInventoryCatalogTest extends TestCase
{
    use RefreshDatabase;

    private StaffRecord $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->owner = StaffRecord::factory()->create();
        $this->owner->assignRole('Shop Owner');
    }

    public function test_an_admin_can_add_a_category_unit_and_supplier(): void
    {
        $this->actingAs($this->owner, 'staff')->post('/admin/inventory/masters', ['kind' => 'category', 'name' => 'Screen protectors'])->assertRedirect();
        $this->actingAs($this->owner, 'staff')->post('/admin/inventory/masters', ['kind' => 'unit', 'name' => 'kegs'])->assertRedirect();
        $this->actingAs($this->owner, 'staff')->post('/admin/inventory/masters', [
            'kind' => 'supplier',
            'name' => 'Acme Wholesale',
            'contact_phone' => '08012345678',
        ])->assertRedirect();

        $this->assertDatabaseHas('inventory_master_entries', ['kind' => 'category', 'name' => 'Screen protectors']);
        $this->assertDatabaseHas('inventory_master_entries', ['kind' => 'unit', 'name' => 'kegs']);
        $this->assertDatabaseHas('inventory_master_entries', ['kind' => 'supplier', 'name' => 'Acme Wholesale', 'contact_phone' => '08012345678']);
    }

    public function test_a_duplicate_name_within_a_list_is_rejected_and_nothing_is_created(): void
    {
        $count = InventoryMasterEntryRecord::query()->where('kind', 'category')->count();

        $this->actingAs($this->owner, 'staff')
            ->from('/admin/inventory/products/create')
            ->post('/admin/inventory/masters', ['kind' => 'category', 'name' => 'Phones'])
            ->assertSessionHasErrors('name');

        $this->assertSame($count, InventoryMasterEntryRecord::query()->where('kind', 'category')->count());
    }

    public function test_a_product_cannot_be_filed_under_a_category_that_is_not_on_the_list(): void
    {
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'MNS']);

        $this->actingAs($this->owner, 'staff')->post('/admin/inventory/products', [
            'brand' => 'Anker',
            'model' => 'PowerCore',
            'category' => 'Made up category',
            'condition' => 'new',
            'is_serialized' => false,
            'cost_price_minor' => 15000,
            'markup_percent' => 20,
            'shop_id' => $shop->id,
            'initial_quantity' => 5,
            'purpose' => 'sale',
        ])->assertSessionHasErrors('category');

        $this->assertDatabaseCount('inventory_products', 0);
    }

    public function test_a_category_or_unit_in_use_cannot_be_deleted(): void
    {
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'MNS']);
        $this->actingAs($this->owner, 'staff')->post('/admin/inventory/products', [
            'brand' => 'Apple',
            'model' => 'iPhone 15',
            'category' => 'Phones',
            'unit' => 'pcs',
            'condition' => 'new',
            'is_serialized' => true,
            'initial_imeis' => ['356938035601001'],
            'cost_price_minor' => 400000,
            'markup_percent' => 15,
            'shop_id' => $shop->id,
            'purpose' => 'sale',
        ])->assertRedirect();

        $phones = InventoryMasterEntryRecord::query()->where('kind', 'category')->where('name', 'Phones')->firstOrFail();

        $this->actingAs($this->owner, 'staff')->delete("/admin/inventory/masters/{$phones->id}")->assertSessionHasErrors('name');

        $this->assertDatabaseHas('inventory_master_entries', ['id' => $phones->id]);
    }

    public function test_renaming_a_category_renames_it_on_every_product_that_uses_it(): void
    {
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'MNS']);
        $this->actingAs($this->owner, 'staff')->post('/admin/inventory/products', [
            'brand' => 'Apple',
            'model' => 'iPhone 15',
            'category' => 'Phones',
            'condition' => 'new',
            'is_serialized' => true,
            'initial_imeis' => ['356938035601001'],
            'cost_price_minor' => 400000,
            'markup_percent' => 15,
            'shop_id' => $shop->id,
            'purpose' => 'sale',
        ])->assertRedirect();

        $phones = InventoryMasterEntryRecord::query()->where('kind', 'category')->where('name', 'Phones')->firstOrFail();

        $this->actingAs($this->owner, 'staff')->put("/admin/inventory/masters/{$phones->id}", ['name' => 'Mobile phones'])->assertRedirect();

        $this->assertSame('Mobile phones', ProductRecord::query()->firstOrFail()->category);
    }

    public function test_a_supplier_can_be_attached_to_a_product_and_is_then_in_use(): void
    {
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'MNS']);
        $this->actingAs($this->owner, 'staff')->post('/admin/inventory/masters', [
            'kind' => 'supplier',
            'name' => 'Acme Wholesale',
            'contact_phone' => '08012345678',
        ]);
        $supplier = InventoryMasterEntryRecord::query()->where('kind', 'supplier')->firstOrFail();

        $this->actingAs($this->owner, 'staff')->post('/admin/inventory/products', [
            'brand' => 'Anker',
            'model' => 'PowerCore',
            'category' => 'Accessories',
            'condition' => 'new',
            'is_serialized' => false,
            'cost_price_minor' => 15000,
            'markup_percent' => 20,
            'shop_id' => $shop->id,
            'initial_quantity' => 5,
            'purpose' => 'both',
            'supplier_id' => $supplier->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('inventory_products', ['supplier_id' => $supplier->id, 'purpose' => 'both', 'unit' => 'pcs']);

        $this->actingAs($this->owner, 'staff')->delete("/admin/inventory/masters/{$supplier->id}")->assertSessionHasErrors('name');
    }
}
