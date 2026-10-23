<?php

declare(strict_types=1);

namespace Tests\Feature\Shop;

use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** STOREFRONT_ENABLED (afprospos.storefront_enabled) lets the storefront be switched off from .env alone — a plain 404, not a special "disabled" page. */
class StorefrontFeatureFlagTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_storefront_is_reachable_when_the_flag_is_on(): void
    {
        config(['afprospos.storefront_enabled' => true]);

        $this->get('/shop')->assertOk();
    }

    public function test_the_storefront_404s_when_the_flag_is_off(): void
    {
        config(['afprospos.storefront_enabled' => false]);

        $this->get('/shop')->assertNotFound();
        $this->get('/shop/1')->assertNotFound();
    }

    public function test_the_customer_cart_404s_when_the_flag_is_off(): void
    {
        config(['afprospos.storefront_enabled' => false]);

        $customer = CustomerRecord::factory()->create();

        $this->actingAs($customer, 'customer')->get('/customer/cart')->assertNotFound();
        $this->actingAs($customer, 'customer')->post('/customer/cart/items', [])->assertNotFound();
    }

    public function test_other_customer_routes_stay_reachable_when_the_storefront_flag_is_off(): void
    {
        config(['afprospos.storefront_enabled' => false]);

        $customer = CustomerRecord::factory()->create();

        $this->actingAs($customer, 'customer')->get('/customer/orders')->assertOk();
    }
}
