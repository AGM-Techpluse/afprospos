<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Sales;

use Domain\Sales\Domain\Entities\SalesCheckoutAdjustment;
use Domain\Sales\Domain\Entities\SalesCheckoutItem;
use Domain\Sales\Domain\Services\CheckoutPricingService;
use Domain\Shared\Domain\ValueObjects\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/** MKT-BR-06/07/08: non-stacking default, deterministic applied_order, discount clamped at subtotal. */
class DiscountStackingTest extends TestCase
{
    public function test_applied_order_follows_the_canonical_mkt_br_07_sequence(): void
    {
        $this->assertSame(1, CheckoutPricingService::appliedOrderFor('promotion'));
        $this->assertSame(2, CheckoutPricingService::appliedOrderFor('referral_voucher'));
        $this->assertSame(3, CheckoutPricingService::appliedOrderFor('trade_in_credit'));
        $this->assertSame(4, CheckoutPricingService::appliedOrderFor('store_credit'));
    }

    public function test_unknown_adjustment_type_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CheckoutPricingService::appliedOrderFor('bogus');
    }

    public function test_recompute_sums_item_line_totals_into_subtotal(): void
    {
        $items = [
            SalesCheckoutItem::create(1, null, 2, new Money(10000)),
            SalesCheckoutItem::create(2, null, 1, new Money(5000)),
        ];

        $totals = CheckoutPricingService::recompute($items, []);

        $this->assertSame(25000, $totals['subtotalMinor']);
        $this->assertSame(0, $totals['discountMinor']);
        $this->assertSame(25000, $totals['totalMinor']);
    }

    public function test_recompute_subtracts_adjustment_amount_from_subtotal(): void
    {
        $items = [SalesCheckoutItem::create(1, null, 1, new Money(10000))];
        $adjustments = [SalesCheckoutAdjustment::create('promotion', 1, new Money(1000))];

        $totals = CheckoutPricingService::recompute($items, $adjustments);

        $this->assertSame(10000, $totals['subtotalMinor']);
        $this->assertSame(1000, $totals['discountMinor']);
        $this->assertSame(9000, $totals['totalMinor']);
    }

    public function test_discount_is_clamped_at_subtotal_never_produces_negative_total(): void
    {
        $items = [SalesCheckoutItem::create(1, null, 1, new Money(1000))];
        $adjustments = [SalesCheckoutAdjustment::create('promotion', 1, new Money(5000))];

        $totals = CheckoutPricingService::recompute($items, $adjustments);

        $this->assertSame(1000, $totals['discountMinor']);
        $this->assertSame(0, $totals['totalMinor']);
    }
}
