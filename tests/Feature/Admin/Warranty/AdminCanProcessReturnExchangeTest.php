<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Warranty;

use Database\Seeders\RolePermissionSeeder;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SaleRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Domain\Warranty\Infrastructure\Persistence\Eloquent\ReturnRequestRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** WAR-BR-13: an approved return may resolve as an exchange instead of a refund. */
class AdminCanProcessReturnExchangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_accountant_can_process_an_approved_return_as_an_exchange(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $accountant = StaffRecord::factory()->create();
        $accountant->assignRole('Accountant');
        $shop = ShopRecord::factory()->create();
        $this->grantShopAccess($accountant, $shop->id);

        $customer = CustomerRecord::factory()->create();
        $sale = SaleRecord::factory()->create(['shop_id' => $shop->id, 'customer_id' => $customer->id, 'total_minor' => 50000]);
        $returnRequest = ReturnRequestRecord::factory()->create([
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'resolution_state' => 'approved',
            'return_window_expires_at' => now()->addDays(5),
        ]);

        $newCheckout = SalesCheckoutRecord::factory()->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($accountant, 'staff')->post(
            "/admin/warranty/returns/{$returnRequest->id}/process-exchange",
            ['new_checkout_id' => $newCheckout->id],
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('return_requests', [
            'id' => $returnRequest->id,
            'resolution_state' => 'resolved',
            'resolution_type' => 'exchange',
            'exchange_checkout_id' => $newCheckout->id,
        ]);
        $this->assertDatabaseHas('sales_checkout_adjustments', [
            'sales_checkout_id' => $newCheckout->id,
            'type' => 'store_credit',
            'source_id' => $returnRequest->id,
            'amount_minor' => 50000,
        ]);
    }

    public function test_a_technician_cannot_process_a_return_exchange(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $technician = StaffRecord::factory()->create();
        $technician->assignRole('Technician'); // warranty.assess/resolve only, not process-exchange

        $sale = SaleRecord::factory()->create();
        $returnRequest = ReturnRequestRecord::factory()->create([
            'sale_id' => $sale->id,
            'resolution_state' => 'approved',
            'return_window_expires_at' => now()->addDays(5),
        ]);
        $newCheckout = SalesCheckoutRecord::factory()->create();

        $response = $this->actingAs($technician, 'staff')->post(
            "/admin/warranty/returns/{$returnRequest->id}/process-exchange",
            ['new_checkout_id' => $newCheckout->id],
        );

        $response->assertForbidden();
        $this->assertDatabaseHas('return_requests', ['id' => $returnRequest->id, 'resolution_state' => 'approved']);
    }

    public function test_processing_an_exchange_against_a_closed_checkout_fails(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $accountant = StaffRecord::factory()->create();
        $accountant->assignRole('Accountant');
        $shop = ShopRecord::factory()->create();
        $this->grantShopAccess($accountant, $shop->id);

        $sale = SaleRecord::factory()->create(['shop_id' => $shop->id]);
        $returnRequest = ReturnRequestRecord::factory()->create([
            'sale_id' => $sale->id,
            'resolution_state' => 'approved',
            'return_window_expires_at' => now()->addDays(5),
        ]);
        $closedCheckout = SalesCheckoutRecord::factory()->create(['status' => 'paid']);

        $response = $this->actingAs($accountant, 'staff')->post(
            "/admin/warranty/returns/{$returnRequest->id}/process-exchange",
            ['new_checkout_id' => $closedCheckout->id],
        );

        $response->assertSessionHasErrors('status');
        $this->assertDatabaseHas('return_requests', ['id' => $returnRequest->id, 'resolution_state' => 'approved']);
    }
}
