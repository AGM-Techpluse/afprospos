<?php

declare(strict_types=1);

namespace Tests\Integration\Sales;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Sales\Application\Commands\AddCheckoutItemCommand;
use Domain\Sales\Application\Commands\CreateCheckoutCommand;
use Domain\Sales\Application\Commands\CreateSaleFromPaidCheckoutCommand;
use Domain\Sales\Application\Commands\RequestCheckoutBankTransferCommand;
use Domain\Sales\Application\Handlers\AddCheckoutItemHandler;
use Domain\Sales\Application\Handlers\CreateCheckoutHandler;
use Domain\Sales\Application\Handlers\CreateSaleFromPaidCheckoutHandler;
use Domain\Sales\Application\Handlers\RequestCheckoutBankTransferHandler;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SaleRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompleteSaleReusesExistingBankTransferTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_completing_a_sale_with_a_pending_customer_transfer_confirms_and_reuses_it(): void
    {
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'LGS']);
        $staff = StaffRecord::factory()->create();
        $customer = CustomerRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create(['selling_price_minor' => 20000]);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 10, 'reserved' => 0]);

        $checkoutId = app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand($shop->id, $customer->id, $staff->id));
        app(AddCheckoutItemHandler::class)->handle(new AddCheckoutItemCommand($checkoutId->value, $sku->id, 1));

        app(RequestCheckoutBankTransferHandler::class)->handle(new RequestCheckoutBankTransferCommand(
            checkoutId: $checkoutId->value,
            customerId: $customer->id,
            transferReference: 'TXN-CUSTOMER-42',
        ));

        $this->assertDatabaseCount('payments_transactions', 1);

        $saleId = app(CreateSaleFromPaidCheckoutHandler::class)->handle(new CreateSaleFromPaidCheckoutCommand(
            checkoutId: $checkoutId->value,
            paymentMethod: 'bank_transfer',
            paymentReference: 'TXN-CUSTOMER-42',
            confirmedByStaffId: $staff->id,
            shopCode: 'LGS',
            existingPaymentTransactionId: $this->pendingTransactionId(),
        ));

        // Still exactly one transaction — confirmed and reused, not duplicated.
        $this->assertDatabaseCount('payments_transactions', 1);
        $this->assertDatabaseHas('payments_transactions', [
            'payable_type' => 'sales_checkout',
            'payable_id' => $checkoutId->value,
            'status' => 'confirmed',
            'confirmed_by_staff_id' => $staff->id,
        ]);

        $sale = SaleRecord::query()->findOrFail($saleId);
        $this->assertSame($this->pendingTransactionId(), $sale->payment_transaction_id);
        $this->assertDatabaseHas('sales_checkouts', ['id' => $checkoutId->value, 'status' => 'paid']);
    }

    private function pendingTransactionId(): int
    {
        /** @var object{id: int} $row */
        $row = DB::table('payments_transactions')->latest('id')->first();

        return $row->id;
    }
}
