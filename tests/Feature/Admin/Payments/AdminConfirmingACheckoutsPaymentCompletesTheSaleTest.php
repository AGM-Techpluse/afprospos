<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Payments;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Payments\Infrastructure\Persistence\Eloquent\PaymentTransactionRecord;
use Domain\Sales\Application\Commands\AddCheckoutItemCommand;
use Domain\Sales\Application\Commands\CreateCheckoutCommand;
use Domain\Sales\Application\Commands\RequestCheckoutBankTransferCommand;
use Domain\Sales\Application\Handlers\AddCheckoutItemHandler;
use Domain\Sales\Application\Handlers\CreateCheckoutHandler;
use Domain\Sales\Application\Handlers\RequestCheckoutBankTransferHandler;
use Domain\Sales\Application\Queries\CustomerOrdersQuery;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A self-service (storefront) checkout has no cashier to separately
 * "complete the sale" at a till — confirming its bank-transfer payment
 * must also finish the order, not just flip the payment transaction's
 * own status while leaving the checkout (and the customer's order view)
 * stuck on "awaiting payment" forever.
 */
class AdminConfirmingACheckoutsPaymentCompletesTheSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirming_a_checkouts_bank_transfer_payment_also_completes_the_sale(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $shop = ShopRecord::factory()->create(['status' => 'active', 'is_warehouse' => false]);
        $customer = CustomerRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create(['selling_price_minor' => 10000]);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 0]);

        $checkoutId = app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand($shop->id, $customer->id, null));
        app(AddCheckoutItemHandler::class)->handle(new AddCheckoutItemCommand($checkoutId->value, $sku->id, 1));

        app(RequestCheckoutBankTransferHandler::class)->handle(new RequestCheckoutBankTransferCommand(
            checkoutId: $checkoutId->value,
            customerId: $customer->id,
            transferReference: 'TXN-1',
        ));

        $transactionId = PaymentTransactionRecord::query()
            ->where('payable_type', 'sales_checkout')
            ->where('payable_id', $checkoutId->value)
            ->value('id');

        $accountant = StaffRecord::factory()->create();
        $accountant->assignRole('Accountant');

        $response = $this->actingAs($accountant, 'staff')->post("/admin/payments/{$transactionId}/confirm", [
            'provider_reference' => 'BANK-OK',
        ]);

        $response->assertRedirect("/admin/payments/{$transactionId}");
        $this->assertDatabaseHas('payments_transactions', [
            'id' => $transactionId,
            'status' => 'confirmed',
            'provider_reference' => 'BANK-OK',
        ]);
        $this->assertDatabaseHas('sales_checkouts', ['id' => $checkoutId->value, 'status' => 'paid']);
        $this->assertDatabaseHas('sales_sales', ['sales_checkout_id' => $checkoutId->value, 'customer_id' => $customer->id]);

        $orders = app(CustomerOrdersQuery::class)->paginate($customer->id, 1);
        $this->assertSame('sale', $orders['data'][0]['type']);
        $this->assertSame('paid', $orders['data'][0]['status']);
    }
}
