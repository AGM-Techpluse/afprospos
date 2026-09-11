<?php

declare(strict_types=1);

namespace Tests\Integration\Sales;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Sales\Application\Commands\AddCheckoutItemCommand;
use Domain\Sales\Application\Commands\CreateCheckoutCommand;
use Domain\Sales\Application\Commands\CreateSaleFromPaidCheckoutCommand;
use Domain\Sales\Application\Handlers\AddCheckoutItemHandler;
use Domain\Sales\Application\Handlers\CreateCheckoutHandler;
use Domain\Sales\Application\Handlers\CreateSaleFromPaidCheckoutHandler;
use Domain\Sales\Domain\Exceptions\CheckoutReservationExpired;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompleteSaleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_completing_a_cash_sale_consumes_reservations_and_writes_an_invoice(): void
    {
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'LGS']);
        $staff = StaffRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create(['selling_price_minor' => 20000]);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 10, 'reserved' => 0]);

        $checkoutId = app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand($shop->id, null, $staff->id));
        app(AddCheckoutItemHandler::class)->handle(new AddCheckoutItemCommand($checkoutId->value, $sku->id, 2));

        $saleId = app(CreateSaleFromPaidCheckoutHandler::class)->handle(new CreateSaleFromPaidCheckoutCommand(
            checkoutId: $checkoutId->value,
            paymentMethod: 'cash',
            paymentReference: null,
            confirmedByStaffId: $staff->id,
            shopCode: 'LGS',
        ));

        $this->assertDatabaseHas('sales_sales', [
            'id' => $saleId,
            'sales_checkout_id' => $checkoutId->value,
            'total_minor' => 40000,
            'payment_method' => 'cash',
            'invoice_number' => 'INV-LGS-000001',
        ]);
        $this->assertDatabaseHas('sales_checkouts', ['id' => $checkoutId->value, 'status' => 'paid']);
        $this->assertDatabaseHas('inventory_stock_levels', ['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 8, 'reserved' => 0]);
    }

    public function test_completing_a_serialized_sale_marks_the_unit_sold(): void
    {
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'LGS']);
        $staff = StaffRecord::factory()->create();
        $sku = SkuRecord::factory()->create(['selling_price_minor' => 50000]);
        $item = InventoryItemRecord::factory()->create(['sku_id' => $sku->id, 'current_shop_id' => $shop->id]);

        $checkoutId = app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand($shop->id, null, $staff->id));
        app(AddCheckoutItemHandler::class)->handle(new AddCheckoutItemCommand($checkoutId->value, $sku->id, 1));

        app(CreateSaleFromPaidCheckoutHandler::class)->handle(new CreateSaleFromPaidCheckoutCommand(
            checkoutId: $checkoutId->value,
            paymentMethod: 'pos_terminal',
            paymentReference: 'TXN-123',
            confirmedByStaffId: $staff->id,
            shopCode: 'LGS',
        ));

        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'status' => 'sold']);
    }

    public function test_completing_an_already_expired_checkout_is_rejected_not_silently_completed(): void
    {
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'LGS']);
        $staff = StaffRecord::factory()->create();

        $checkout = SalesCheckoutRecord::factory()->expired()->create([
            'shop_id' => $shop->id,
            'cashier_staff_id' => $staff->id,
        ]);

        $this->expectException(CheckoutReservationExpired::class);

        app(CreateSaleFromPaidCheckoutHandler::class)->handle(new CreateSaleFromPaidCheckoutCommand(
            checkoutId: $checkout->id,
            paymentMethod: 'cash',
            paymentReference: null,
            confirmedByStaffId: $staff->id,
            shopCode: 'LGS',
        ));
    }
}
