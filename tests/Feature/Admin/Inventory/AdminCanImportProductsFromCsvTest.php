<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Inventory;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminCanImportProductsFromCsvTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_row_gets_a_result_even_when_trailing_optional_columns_are_omitted(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'MNS']);

        // Row 2 and row 3 both drop the trailing comma for the unused
        // optional column (imei / low_stock_threshold) — a common
        // spreadsheet-export quirk that must not make the row vanish.
        // Row 4 has every column present but a blank brand, which must
        // surface as an explicit failure rather than a silent skip.
        $csv = implode("\n", [
            'brand,model,category,condition,cost_price_minor,markup_percent,quantity,imei,low_stock_threshold',
            'Apple,iPhone 15,Phones,new,400000,15,,123456789012345',
            'Anker,PowerCore 10000,Accessories,new,15000,20,25,',
            ',Nameless,Accessories,new,1000,10,5,,',
        ]);

        $file = UploadedFile::fake()->createWithContent('products.csv', $csv);

        $response = $this->actingAs($owner, 'staff')->post('/admin/inventory/import', [
            'shop_id' => $shop->id,
            'file' => $file,
        ]);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Inventory/Import/Results')
            ->has('results', 3)
            ->where('results.0.succeeded', true)
            ->where('results.0.sku_code', 'MNS-PHO-000001')
            ->where('results.1.succeeded', true)
            ->where('results.2.succeeded', false)
            ->whereNot('results.2.error_message', null));

        $this->assertDatabaseHas('inventory_products', ['brand' => 'Apple', 'model' => 'iPhone 15']);
        $this->assertDatabaseHas('inventory_products', ['brand' => 'Anker', 'model' => 'PowerCore 10000']);
        $this->assertDatabaseMissing('inventory_products', ['model' => 'Nameless']);
    }

    public function test_a_duplicate_imei_fails_the_row_with_a_friendly_message_not_a_raw_sql_error(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'MNS']);
        $existingSku = SkuRecord::factory()->create();
        InventoryItemRecord::factory()->create(['sku_id' => $existingSku->id, 'imei' => '123456789012345']);

        $csv = implode("\n", [
            'brand,model,category,condition,cost_price_minor,markup_percent,quantity,imei,low_stock_threshold',
            'Apple,iPhone 15,Phones,new,400000,15,,123456789012345,',
        ]);

        $file = UploadedFile::fake()->createWithContent('products.csv', $csv);

        $response = $this->actingAs($owner, 'staff')->post('/admin/inventory/import', [
            'shop_id' => $shop->id,
            'file' => $file,
        ]);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('results', 1)
            ->where('results.0.succeeded', false)
            ->where('results.0.error_message', 'An inventory item already exists with IMEI [123456789012345].'));
    }
}
