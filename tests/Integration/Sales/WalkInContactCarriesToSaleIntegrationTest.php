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
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalkInContactCarriesToSaleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_walk_in_contact_captured_at_checkout_is_persisted_and_carried_to_the_completed_sale(): void
    {
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'LGS']);
        $staff = StaffRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create(['selling_price_minor' => 20000]);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 10, 'reserved' => 0]);

        $checkoutId = app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand(
            shopId: $shop->id,
            customerId: null,
            cashierStaffId: $staff->id,
            walkInName: 'Chidi Okafor',
            walkInPhone: '08012345678',
            walkInEmail: 'chidi@example.com',
        ));

        $this->assertDatabaseHas('sales_checkouts', [
            'id' => $checkoutId->value,
            'customer_id' => null,
            'walk_in_name' => 'Chidi Okafor',
            'walk_in_phone' => '08012345678',
            'walk_in_email' => 'chidi@example.com',
        ]);

        app(AddCheckoutItemHandler::class)->handle(new AddCheckoutItemCommand($checkoutId->value, $sku->id, 1));

        $saleId = app(CreateSaleFromPaidCheckoutHandler::class)->handle(new CreateSaleFromPaidCheckoutCommand(
            checkoutId: $checkoutId->value,
            paymentMethod: 'cash',
            paymentReference: null,
            confirmedByStaffId: $staff->id,
            shopCode: 'LGS',
        ));

        $this->assertDatabaseHas('sales_sales', [
            'id' => $saleId,
            'customer_id' => null,
            'walk_in_name' => 'Chidi Okafor',
            'walk_in_phone' => '08012345678',
            'walk_in_email' => 'chidi@example.com',
        ]);
    }
}
