<?php

declare(strict_types=1);

namespace Tests\Feature\Customer\Shop;

use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutItemRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** A "Cannot" test asserts both the rejection and that nothing about the record changed. */
class CustomerCannotManageAnotherCustomersCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_cannot_update_or_remove_another_customers_cart_item(): void
    {
        $owner = CustomerRecord::factory()->create();
        $intruder = CustomerRecord::factory()->create();
        $shop = ShopRecord::factory()->create();
        $checkout = SalesCheckoutRecord::factory()->create(['customer_id' => $owner->id, 'shop_id' => $shop->id, 'status' => 'open']);
        $item = SalesCheckoutItemRecord::factory()->create(['sales_checkout_id' => $checkout->id, 'quantity' => 1]);

        $this->actingAs($intruder, 'customer')
            ->patch("/customer/cart/items/{$item->id}", ['quantity' => 9])
            ->assertNotFound();
        $this->assertDatabaseHas('sales_checkout_items', ['id' => $item->id, 'quantity' => 1]);

        $this->actingAs($intruder, 'customer')
            ->delete("/customer/cart/items/{$item->id}")
            ->assertNotFound();
        $this->assertDatabaseHas('sales_checkout_items', ['id' => $item->id]);
    }
}
