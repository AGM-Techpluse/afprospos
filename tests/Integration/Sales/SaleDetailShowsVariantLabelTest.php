<?php

declare(strict_types=1);

namespace Tests\Integration\Sales;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Sales\Application\Commands\AddCheckoutItemCommand;
use Domain\Sales\Application\Commands\CreateCheckoutCommand;
use Domain\Sales\Application\Commands\CreateSaleFromPaidCheckoutCommand;
use Domain\Sales\Application\Handlers\AddCheckoutItemHandler;
use Domain\Sales\Application\Handlers\CreateCheckoutHandler;
use Domain\Sales\Application\Handlers\CreateSaleFromPaidCheckoutHandler;
use Domain\Sales\Application\Queries\SaleDetailQuery;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The receipt and the sale detail page must be able to tell a variant apart (e.g. two colours of the same phone), not just show the bare product name. */
class SaleDetailShowsVariantLabelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_sold_variant_carries_its_attribute_label_through_to_the_sale_detail(): void
    {
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'LGS']);
        $staff = StaffRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create([
            'selling_price_minor' => 20000,
            'attributes' => ['color' => 'Black', 'custom' => ['storage' => '128GB']],
        ]);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 0]);

        $checkoutId = app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand($shop->id, null, $staff->id));
        app(AddCheckoutItemHandler::class)->handle(new AddCheckoutItemCommand($checkoutId->value, $sku->id, 1));

        $saleId = app(CreateSaleFromPaidCheckoutHandler::class)->handle(new CreateSaleFromPaidCheckoutCommand(
            checkoutId: $checkoutId->value,
            paymentMethod: 'cash',
            paymentReference: null,
            confirmedByStaffId: $staff->id,
            shopCode: 'LGS',
        ));

        $sale = app(SaleDetailQuery::class)->find($saleId);

        $this->assertSame('Black · 128GB', $sale['items'][0]['variant_label']);
    }

    public function test_a_plain_product_with_no_variant_attributes_has_an_empty_label(): void
    {
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'LGS']);
        $staff = StaffRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create(['selling_price_minor' => 20000, 'attributes' => []]);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 0]);

        $checkoutId = app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand($shop->id, null, $staff->id));
        app(AddCheckoutItemHandler::class)->handle(new AddCheckoutItemCommand($checkoutId->value, $sku->id, 1));

        $saleId = app(CreateSaleFromPaidCheckoutHandler::class)->handle(new CreateSaleFromPaidCheckoutCommand(
            checkoutId: $checkoutId->value,
            paymentMethod: 'cash',
            paymentReference: null,
            confirmedByStaffId: $staff->id,
            shopCode: 'LGS',
        ));

        $sale = app(SaleDetailQuery::class)->find($saleId);

        $this->assertSame('', $sale['items'][0]['variant_label']);
    }
}
