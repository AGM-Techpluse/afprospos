<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Sales;

use Database\Seeders\RolePermissionSeeder;
use Domain\Sales\Application\Commands\RequestCheckoutBankTransferCommand;
use Domain\Sales\Application\Handlers\RequestCheckoutBankTransferHandler;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminSeesCustomerSubmittedTransferOnCompleteFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_sees_the_pending_customer_transfer_and_completing_reuses_it(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'MNS']);
        $customer = CustomerRecord::factory()->create();

        $checkout = SalesCheckoutRecord::factory()->create([
            'shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'cashier_staff_id' => $cashier->id,
            'status' => 'open',
            'total_minor' => 30000,
        ]);

        app(RequestCheckoutBankTransferHandler::class)->handle(new RequestCheckoutBankTransferCommand(
            checkoutId: $checkout->id,
            customerId: $customer->id,
            transferReference: 'TXN-SEEN-BY-STAFF',
        ));

        $response = $this->actingAs($cashier, 'staff')->get("/admin/sales/checkout?checkout={$checkout->id}");

        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Sales/Checkout')
            ->where('pendingPaymentTransaction.method', 'bank_transfer')
            ->where('pendingPaymentTransaction.provider_reference', 'TXN-SEEN-BY-STAFF'));

        $transactionId = (int) DB::table('payments_transactions')->latest('id')->value('id');

        $complete = $this->actingAs($cashier, 'staff')->post("/admin/sales/checkout/{$checkout->id}/complete", [
            'payment_method' => 'bank_transfer',
            'payment_reference' => 'TXN-SEEN-BY-STAFF',
            'existing_payment_transaction_id' => $transactionId,
        ]);

        $complete->assertRedirect();
        $this->assertDatabaseCount('payments_transactions', 1);
        $this->assertDatabaseHas('payments_transactions', ['id' => $transactionId, 'status' => 'confirmed']);
    }
}
