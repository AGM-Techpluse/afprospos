<?php

declare(strict_types=1);

namespace Tests\Feature\Customer\Shop;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutItemRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCanManageOwnCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_can_add_update_and_remove_their_own_cart_items(): void
    {
        $customer = CustomerRecord::factory()->create();
        $shop = ShopRecord::factory()->create(['status' => 'active', 'is_warehouse' => false]);
        $sku = SkuRecord::factory()->nonSerialized()->create();
        $sku->product()->update(['purpose' => 'sale']);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 0]);

        $response = $this->actingAs($customer, 'customer')->post('/customer/cart/items', [
            'sku_id' => $sku->id,
            'shop_id' => $shop->id,
            'quantity' => 2,
        ]);

        $response->assertRedirect();

        $checkout = SalesCheckoutRecord::query()->where('customer_id', $customer->id)->firstOrFail();
        $this->assertSame('open', $checkout->status);
        $this->assertNull($checkout->cashier_staff_id);

        $item = SalesCheckoutItemRecord::query()->where('sales_checkout_id', $checkout->id)->firstOrFail();
        $this->assertSame(2, $item->quantity);

        $this->actingAs($customer, 'customer')
            ->patch("/customer/cart/items/{$item->id}", ['quantity' => 5])
            ->assertRedirect();

        $this->assertDatabaseHas('sales_checkout_items', ['id' => $item->id, 'quantity' => 5]);

        $this->actingAs($customer, 'customer')
            ->delete("/customer/cart/items/{$item->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('sales_checkout_items', ['id' => $item->id]);
    }

    public function test_a_guest_is_redirected_to_login_and_cannot_add_to_cart(): void
    {
        $shop = ShopRecord::factory()->create(['status' => 'active', 'is_warehouse' => false]);
        $sku = SkuRecord::factory()->nonSerialized()->create();
        $sku->product()->update(['purpose' => 'sale']);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 0]);

        $response = $this->post('/customer/cart/items', [
            'sku_id' => $sku->id,
            'shop_id' => $shop->id,
            'quantity' => 1,
        ]);

        $response->assertRedirect('/customer/login');
        $this->assertDatabaseMissing('sales_checkouts', ['shop_id' => $shop->id]);
    }
}
