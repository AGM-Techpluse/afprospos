<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Inventory;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Domain\Services\AttributeSignature;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\ProductRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanUpdateSkuAttributesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_skus_attributes_can_be_corrected(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $sku = SkuRecord::factory()->create(['attributes' => ['storage' => '128GB']]);

        $response = $this->actingAs($owner, 'staff')->put("/admin/inventory/products/{$sku->id}/attributes", [
            'attributes' => ['storage' => '256GB', 'color' => 'Midnight'],
        ]);

        $response->assertRedirect();
        $sku->refresh();
        $this->assertSame(['storage' => '256GB', 'color' => 'Midnight'], $sku->attributes);
        $this->assertSame(AttributeSignature::of(['storage' => '256GB', 'color' => 'Midnight']), $sku->attributes_signature);
    }

    public function test_cannot_edit_attributes_to_match_a_different_sibling_sku(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $product = ProductRecord::factory()->create();

        $siblingAttributes = ['storage' => '256GB'];
        SkuRecord::factory()->create([
            'product_id' => $product->id,
            'attributes' => $siblingAttributes,
            'attributes_signature' => AttributeSignature::of($siblingAttributes),
            'is_serialized' => true,
        ]);
        $sku = SkuRecord::factory()->create([
            'product_id' => $product->id,
            'attributes' => ['storage' => '128GB'],
            'attributes_signature' => AttributeSignature::of(['storage' => '128GB']),
            'is_serialized' => true,
        ]);

        $response = $this->actingAs($owner, 'staff')
            ->from("/admin/inventory/products/{$sku->id}")
            ->put("/admin/inventory/products/{$sku->id}/attributes", ['attributes' => $siblingAttributes]);

        $response->assertSessionHasErrors('attributes');
        $sku->refresh();
        $this->assertSame(['storage' => '128GB'], $sku->attributes);
    }

    public function test_blocks_a_staff_member_without_inventory_edit(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $sku = SkuRecord::factory()->create(['attributes' => ['storage' => '128GB']]);

        $response = $this->actingAs($cashier, 'staff')->put("/admin/inventory/products/{$sku->id}/attributes", [
            'attributes' => ['storage' => '256GB'],
        ]);

        $response->assertForbidden();
        $sku->refresh();
        $this->assertSame(['storage' => '128GB'], $sku->attributes);
    }
}
