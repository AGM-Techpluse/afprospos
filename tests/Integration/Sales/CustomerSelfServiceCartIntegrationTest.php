<?php

declare(strict_types=1);

namespace Tests\Integration\Sales;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Sales\Application\Commands\AddCheckoutItemCommand;
use Domain\Sales\Application\Commands\CreateCheckoutCommand;
use Domain\Sales\Application\Handlers\AddCheckoutItemHandler;
use Domain\Sales\Application\Handlers\CreateCheckoutHandler;
use Domain\Sales\Domain\Exceptions\CustomerCartReservedAtAnotherShop;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The storefront's self-service cart (no cashier) — a longer, renewing
 * reservation window than a POS checkout's, and a till can claim one
 * instead of leaving it to rot unclaimed once the customer walks in.
 */
class CustomerSelfServiceCartIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_self_service_open_uses_the_longer_reservation_window(): void
    {
        $shop = ShopRecord::factory()->create();
        $customer = CustomerRecord::factory()->create();

        $id = app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand(
            shopId: $shop->id,
            customerId: $customer->id,
            cashierStaffId: null,
        ));

        $checkout = SalesCheckoutRecord::query()->findOrFail($id->value);

        $this->assertNull($checkout->cashier_staff_id);
        $this->assertTrue($checkout->reservation_expires_at->greaterThan(Carbon::now()->addHours(23)));
    }

    public function test_a_customer_self_service_open_resumes_their_existing_open_cart(): void
    {
        $shop = ShopRecord::factory()->create();
        $customer = CustomerRecord::factory()->create();

        $firstId = app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand($shop->id, $customer->id, null));
        $secondId = app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand($shop->id, $customer->id, null));

        $this->assertSame($firstId->value, $secondId->value);
        $this->assertSame(1, SalesCheckoutRecord::query()->where('customer_id', $customer->id)->count());
    }

    public function test_a_till_claims_an_existing_customer_cart_at_the_same_shop_instead_of_duplicating_it(): void
    {
        $shop = ShopRecord::factory()->create();
        $customer = CustomerRecord::factory()->create();
        $staff = StaffRecord::factory()->create();

        $customerCartId = app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand($shop->id, $customer->id, null));

        $claimedId = app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand($shop->id, $customer->id, $staff->id));

        $this->assertSame($customerCartId->value, $claimedId->value);
        $this->assertDatabaseHas('sales_checkouts', [
            'id' => $customerCartId->value,
            'cashier_staff_id' => $staff->id,
        ]);
        $this->assertSame(1, SalesCheckoutRecord::query()->where('customer_id', $customer->id)->count());
    }

    public function test_a_till_cannot_silently_absorb_a_customer_carts_reserved_at_a_different_shop(): void
    {
        $customerShop = ShopRecord::factory()->create();
        $tillShop = ShopRecord::factory()->create();
        $customer = CustomerRecord::factory()->create();
        $staff = StaffRecord::factory()->create();

        app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand($customerShop->id, $customer->id, null));

        $this->expectException(CustomerCartReservedAtAnotherShop::class);

        app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand($tillShop->id, $customer->id, $staff->id));
    }

    public function test_a_customer_cannot_silently_add_a_second_product_from_a_different_shop_to_their_own_open_cart(): void
    {
        $firstShop = ShopRecord::factory()->create();
        $secondShop = ShopRecord::factory()->create();
        $customer = CustomerRecord::factory()->create();

        app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand($firstShop->id, $customer->id, null));

        $this->expectException(CustomerCartReservedAtAnotherShop::class);

        app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand($secondShop->id, $customer->id, null));
    }

    public function test_adding_an_item_renews_and_persists_a_customer_carts_reservation_window(): void
    {
        $shop = ShopRecord::factory()->create();
        $customer = CustomerRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create();
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 0]);

        $checkoutId = app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand($shop->id, $customer->id, null));

        // Simulate the window having decayed since it was opened.
        SalesCheckoutRecord::query()->whereKey($checkoutId->value)->update(['reservation_expires_at' => Carbon::now()->addHour()]);

        app(AddCheckoutItemHandler::class)->handle(new AddCheckoutItemCommand($checkoutId->value, $sku->id, 1));

        $checkout = SalesCheckoutRecord::query()->findOrFail($checkoutId->value);
        $this->assertTrue($checkout->reservation_expires_at->greaterThan(Carbon::now()->addHours(23)));
    }

    public function test_adding_an_item_does_not_renew_a_pos_checkouts_reservation_window(): void
    {
        $shop = ShopRecord::factory()->create();
        $staff = StaffRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create();
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 0]);

        $checkoutId = app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand($shop->id, null, $staff->id));
        $originalExpiry = SalesCheckoutRecord::query()->findOrFail($checkoutId->value)->reservation_expires_at;

        app(AddCheckoutItemHandler::class)->handle(new AddCheckoutItemCommand($checkoutId->value, $sku->id, 1));

        $checkout = SalesCheckoutRecord::query()->findOrFail($checkoutId->value);
        $this->assertTrue($checkout->reservation_expires_at->equalTo($originalExpiry));
        $this->assertTrue($checkout->reservation_expires_at->lessThan(Carbon::now()->addHours(23)));
    }
}
