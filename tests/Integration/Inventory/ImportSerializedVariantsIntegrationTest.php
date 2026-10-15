<?php

declare(strict_types=1);

namespace Tests\Integration\Inventory;

use Domain\Inventory\Application\Commands\ImportProductsCommand;
use Domain\Inventory\Application\DTOs\ProductImportRow;
use Domain\Inventory\Application\Handlers\ImportProductsHandler;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\ProductRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A serialized device is one SKU per product configuration (brand+model+category+
 * color/storage/...), and each IMEI is an item under it — the same rule the single-
 * product form follows (INV-BR-08/16/18). Imports must match existing SKUs the same way.
 */
class ImportSerializedVariantsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_configuration_with_different_imeis_is_one_sku(): void
    {
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'MAIN']);
        $staff = StaffRecord::factory()->create();

        $results = $this->import($shop, $staff->id, [
            $this->row(2, imei: '356938035601001', attributes: ['color' => 'Black', 'custom' => ['storage' => '256GB']]),
            $this->row(3, imei: '356938035601002', attributes: ['color' => 'black', 'custom' => ['storage' => '256gb']]),
        ]);

        $this->assertTrue($results[0]->succeeded);
        $this->assertTrue($results[1]->succeeded);
        $this->assertSame(1, SkuRecord::query()->count());
        $this->assertSame(2, InventoryItemRecord::query()->count());
    }

    public function test_a_different_color_is_a_new_sku_under_the_same_product(): void
    {
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'MAIN']);
        $staff = StaffRecord::factory()->create();

        $this->import($shop, $staff->id, [
            $this->row(2, imei: '356938035601001', attributes: ['color' => 'Black', 'custom' => ['storage' => '256GB']]),
            $this->row(3, imei: '356938035601002', attributes: ['color' => 'White', 'custom' => ['storage' => '256GB']]),
        ]);

        $this->assertSame(2, SkuRecord::query()->count());
        $this->assertSame(1, ProductRecord::query()->count());
    }

    public function test_the_same_imei_is_still_rejected_and_creates_nothing(): void
    {
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'MAIN']);
        $staff = StaffRecord::factory()->create();

        $results = $this->import($shop, $staff->id, [
            $this->row(2, imei: '356938035601001', attributes: ['color' => 'Black']),
            $this->row(3, imei: '356938035601001', attributes: ['color' => 'Black']),
        ]);

        $this->assertTrue($results[0]->succeeded);
        $this->assertFalse($results[1]->succeeded);
        $this->assertSame(1, InventoryItemRecord::query()->count());
    }

    /** @param  ProductImportRow[]  $rows */
    private function import(ShopRecord $shop, int $staffId, array $rows): array
    {
        return app(ImportProductsHandler::class)->handle(new ImportProductsCommand(
            rows: $rows,
            shopCode: $shop->sku_prefix_code,
            shopId: $shop->id,
            importedByStaffId: $staffId,
        ));
    }

    /** @param  array<string, mixed>  $attributes */
    private function row(int $rowNumber, string $imei, array $attributes): ProductImportRow
    {
        return new ProductImportRow(
            rowNumber: $rowNumber,
            brand: 'Apple',
            model: 'iPhone 15',
            category: 'Phones',
            condition: 'new',
            costPriceMinor: 400000,
            markupPercent: 15.0,
            quantity: null,
            imei: $imei,
            lowStockThreshold: null,
            attributes: $attributes,
        );
    }
}
