<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Inventory;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Domain\Services\AttributeSignature;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\ProductRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * The documented demo file (documentation/afprospos-demo-rich-import.csv) imports
 * every row: serialized phones with repeated configurations (one SKU, many IMEIs),
 * non-serialized accessories with colour variants, and repair parts.
 */
class AdminCanImportTheRichDemoCsvTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_row_of_the_demo_file_imports(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'DEM']);

        $file = new UploadedFile(base_path('documentation/afprospos-demo-rich-import.csv'), 'afprospos-demo-rich-import.csv', 'text/csv', null, true);

        $response = $this->actingAs($owner, 'staff')->post('/admin/inventory/import', [
            'shop_id' => $shop->id,
            'file' => $file,
        ]);

        $response->assertOk();
        $response->assertInertia(function ($page): void {
            $results = $page->toArray()['props']['results'];
            $failed = array_values(array_filter($results, fn (array $row): bool => ! $row['succeeded']));

            $this->assertCount(116, $results);
            $this->assertSame([], $failed, 'Failed rows: '.json_encode($failed));
        });

        // One product per distinct brand+model; iPhone 15 comes in several colour/storage configurations.
        $this->assertDatabaseHas('inventory_products', ['brand' => 'Apple', 'model' => 'iPhone 15', 'purpose' => 'sale']);
        $this->assertDatabaseHas('inventory_products', ['brand' => 'Apple', 'model' => 'iPhone 13 OLED Screen', 'purpose' => 'both']);
        $this->assertGreaterThan(0, ProductRecord::query()->where('purpose', 'repair_part')->count());

        // Same configuration repeated => one SKU with several IMEI items (not one SKU per IMEI).
        $iphone15Black128 = SkuRecord::query()
            ->whereHas('product', fn ($q) => $q->where('model', 'iPhone 15'))
            ->where('is_serialized', true)
            ->where('attributes_signature', AttributeSignature::of(['color' => 'Black', 'custom' => ['storage' => '128GB']]))
            ->firstOrFail();
        $this->assertSame(2, InventoryItemRecord::query()->where('sku_id', $iphone15Black128->id)->count());

        // Every serialized row (one with an IMEI) becomes exactly one inventory item.
        $lines = array_slice(file(base_path('documentation/afprospos-demo-rich-import.csv'), FILE_IGNORE_NEW_LINES), 1);
        $serializedRows = count(array_filter($lines, fn (string $line): bool => trim(str_getcsv($line)[7] ?? '') !== ''));
        $this->assertSame($serializedRows, InventoryItemRecord::query()->count());
    }
}
