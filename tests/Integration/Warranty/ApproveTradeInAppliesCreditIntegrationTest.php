<?php

declare(strict_types=1);

namespace Tests\Integration\Warranty;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Sales\Application\Commands\AddCheckoutItemCommand;
use Domain\Sales\Application\Commands\CreateCheckoutCommand;
use Domain\Sales\Application\Handlers\AddCheckoutItemHandler;
use Domain\Sales\Application\Handlers\CreateCheckoutHandler;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Domain\Warranty\Application\Commands\ApproveTradeInCommand;
use Domain\Warranty\Application\Commands\AssessTradeInCommand;
use Domain\Warranty\Application\Handlers\ApproveTradeInHandler;
use Domain\Warranty\Application\Handlers\AssessTradeInHandler;
use Domain\Warranty\Domain\Exceptions\TradeInApproverMustDifferFromAssessor;
use Domain\Warranty\Infrastructure\Persistence\Eloquent\TradeInAssessmentRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApproveTradeInAppliesCreditIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_a_trade_in_linked_to_an_open_checkout_applies_the_credit_and_reduces_the_total(): void
    {
        $shop = ShopRecord::factory()->create();
        $cashier = StaffRecord::factory()->create();
        $assessor = StaffRecord::factory()->create();
        $approver = StaffRecord::factory()->create();
        $customer = CustomerRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create(['selling_price_minor' => 100000]);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 10, 'reserved' => 0]);

        $checkoutId = app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand($shop->id, $customer->id, $cashier->id));
        app(AddCheckoutItemHandler::class)->handle(new AddCheckoutItemCommand($checkoutId->value, $sku->id, 1));

        $tradeIn = TradeInAssessmentRecord::factory()->create([
            'customer_id' => $customer->id,
            'related_checkout_id' => $checkoutId->value,
        ]);

        app(AssessTradeInHandler::class)->handle(new AssessTradeInCommand(
            tradeInAssessmentId: $tradeIn->id,
            assessedValueMinor: 35000,
            assessedByStaffId: $assessor->id,
        ));

        app(ApproveTradeInHandler::class)->handle(new ApproveTradeInCommand(
            tradeInAssessmentId: $tradeIn->id,
            approvedByStaffId: $approver->id,
        ));

        $this->assertDatabaseHas('trade_in_assessments', [
            'id' => $tradeIn->id,
            'resolution_state' => 'applied',
        ]);

        $this->assertDatabaseHas('sales_checkout_adjustments', [
            'sales_checkout_id' => $checkoutId->value,
            'type' => 'trade_in_credit',
            'source_id' => $tradeIn->id,
            'amount_minor' => 35000,
        ]);

        $this->assertDatabaseHas('sales_checkouts', [
            'id' => $checkoutId->value,
            'total_minor' => 100000 - 35000,
        ]);
    }

    public function test_the_same_staff_member_cannot_assess_and_approve_a_trade_in(): void
    {
        $customer = CustomerRecord::factory()->create();
        $staff = StaffRecord::factory()->create();

        $tradeIn = TradeInAssessmentRecord::factory()->create(['customer_id' => $customer->id]);

        app(AssessTradeInHandler::class)->handle(new AssessTradeInCommand(
            tradeInAssessmentId: $tradeIn->id,
            assessedValueMinor: 35000,
            assessedByStaffId: $staff->id,
        ));

        $this->expectException(TradeInApproverMustDifferFromAssessor::class);

        try {
            app(ApproveTradeInHandler::class)->handle(new ApproveTradeInCommand(
                tradeInAssessmentId: $tradeIn->id,
                approvedByStaffId: $staff->id,
            ));
        } finally {
            $this->assertDatabaseHas('trade_in_assessments', ['id' => $tradeIn->id, 'resolution_state' => 'assessed']);
        }
    }
}
