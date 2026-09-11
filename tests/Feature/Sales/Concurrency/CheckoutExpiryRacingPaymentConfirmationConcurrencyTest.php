<?php

declare(strict_types=1);

namespace Tests\Feature\Sales\Concurrency;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutItemRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Symfony\Component\Process\Process;
use Tests\Support\UsesConcurrencyDatabase;
use Tests\TestCase;

/**
 * Phase 4 exit criterion / SALE/PAY-BR-08: "a payment confirmation
 * received after its reservation has already expired shall not
 * automatically complete the sale against released inventory... shall
 * instead be routed to a configured payment-exception process." Two
 * real OS processes race the expiry worker against a payment
 * confirmation on the same checkout — DBDD §40's invariant ("expired
 * checkout cannot become paid") is what the SELECT ... FOR UPDATE on
 * sales_checkouts makes true.
 */
class CheckoutExpiryRacingPaymentConfirmationConcurrencyTest extends TestCase
{
    use UsesConcurrencyDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpConcurrencyDatabase();
    }

    protected function tearDown(): void
    {
        $this->tearDownConcurrencyDatabase([
            'sales_sales', 'sales_checkout_adjustments', 'sales_checkout_items', 'sales_checkouts',
            'inventory_stock_levels', 'inventory_skus', 'inventory_products', 'staff', 'shops',
        ]);
        parent::tearDown();
    }

    public function test_expiry_and_payment_confirmation_never_both_succeed_on_the_same_checkout(): void
    {
        $shop = ShopRecord::factory()->create(['sku_prefix_code' => 'LGS']);
        $staff = StaffRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create();
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 1]);

        $checkout = SalesCheckoutRecord::factory()->create([
            'shop_id' => $shop->id,
            'cashier_staff_id' => $staff->id,
            'status' => 'open',
        ]);
        SalesCheckoutItemRecord::factory()->create([
            'sales_checkout_id' => $checkout->id,
            'sku_id' => $sku->id,
            'quantity' => 1,
            'unit_price_minor' => 10000,
        ]);

        $expireProcess = $this->buildProcess($checkout->id, $staff->id, 'expire', holdMs: 400);
        $completeProcess = $this->buildProcess($checkout->id, $staff->id, 'complete', holdMs: 0);

        $expireProcess->start();
        usleep(50_000);
        $completeProcess->start();

        $expireProcess->wait();
        $completeProcess->wait();

        $expireResult = json_decode($expireProcess->getOutput(), true);
        $completeResult = json_decode($completeProcess->getOutput(), true);

        $successCount = (int) ($expireResult['success'] ?? false) + (int) ($completeResult['success'] ?? false);

        $this->assertSame(
            1,
            $successCount,
            "Expected exactly one of expire/complete to succeed.\n".
            'Expire: '.$expireProcess->getOutput().' '.$expireProcess->getErrorOutput()."\n".
            'Complete: '.$completeProcess->getOutput().' '.$completeProcess->getErrorOutput(),
        );

        $freshCheckout = SalesCheckoutRecord::query()->findOrFail($checkout->id);
        $this->assertSame('expired', $freshCheckout->status, 'a checkout the worker won the race to expire must not end up paid (DBDD §40).');
        $this->assertDatabaseMissing('sales_sales', ['sales_checkout_id' => $checkout->id]);

        $level = InventoryStockLevelRecord::query()->where('sku_id', $sku->id)->where('shop_id', $shop->id)->firstOrFail();
        $this->assertSame(0, $level->reserved, 'the reservation must be released back to Available, never left dangling or double-released.');
    }

    private function buildProcess(int $checkoutId, int $staffId, string $action, int $holdMs): Process
    {
        return new Process([
            PHP_BINARY,
            __DIR__.'/Support/attempt_expire_or_complete.php',
            "--checkout={$checkoutId}",
            "--action={$action}",
            "--staff={$staffId}",
            '--shop-code=LGS',
            "--hold-ms={$holdMs}",
        ], timeout: 15);
    }
}
