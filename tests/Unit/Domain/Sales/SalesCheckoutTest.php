<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Sales;

use DateTimeImmutable;
use Domain\Sales\Domain\Entities\SalesCheckout;
use Domain\Sales\Domain\Entities\SalesCheckoutAdjustment;
use Domain\Sales\Domain\Entities\SalesCheckoutItem;
use Domain\Sales\Domain\Exceptions\CheckoutNotOpen;
use Domain\Sales\Domain\Exceptions\CheckoutReservationExpired;
use Domain\Sales\Domain\ValueObjects\CheckoutId;
use Domain\Sales\Domain\ValueObjects\SalesCheckoutItemId;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;
use PHPUnit\Framework\TestCase;

class SalesCheckoutTest extends TestCase
{
    private function openCheckout(?DateTimeImmutable $expiresAt = null): SalesCheckout
    {
        return SalesCheckout::open(new ShopId(1), null, new StaffId(1), $expiresAt ?? new DateTimeImmutable('+15 minutes'));
    }

    public function test_add_item_recomputes_totals(): void
    {
        $checkout = $this->openCheckout();
        $checkout->addItem(SalesCheckoutItem::create(1, null, 2, new Money(5000)));

        $this->assertSame(10000, $checkout->subtotalMinor());
        $this->assertSame(10000, $checkout->totalMinor());
    }

    public function test_remove_item_recomputes_totals_and_returns_removed_item(): void
    {
        $checkout = SalesCheckout::reconstitute(
            new CheckoutId(1),
            new ShopId(1),
            null,
            new StaffId(1),
            'open',
            new DateTimeImmutable('+15 minutes'),
            [SalesCheckoutItem::reconstitute(new SalesCheckoutItemId(9), 1, null, 1, new Money(5000))],
            [],
            5000,
            0,
            5000,
        );

        $removed = $checkout->removeItem(new SalesCheckoutItemId(9));

        $this->assertSame(1, $removed->skuId());
        $this->assertSame(0, $checkout->subtotalMinor());
        $this->assertSame([], $checkout->items());
    }

    public function test_apply_adjustment_replaces_any_existing_one(): void
    {
        $checkout = $this->openCheckout();
        $checkout->addItem(SalesCheckoutItem::create(1, null, 1, new Money(10000)));

        $checkout->applyAdjustment(SalesCheckoutAdjustment::create('promotion', 1, new Money(1000)));
        $checkout->applyAdjustment(SalesCheckoutAdjustment::create('store_credit', 2, new Money(2000)));

        $this->assertCount(1, $checkout->adjustments());
        $this->assertSame('store_credit', $checkout->adjustments()[0]->type());
        $this->assertSame(2000, $checkout->discountMinor());
    }

    public function test_cancel_transitions_status_to_cancelled(): void
    {
        $checkout = $this->openCheckout();
        $checkout->cancel();

        $this->assertSame('cancelled', $checkout->status());
    }

    public function test_add_item_refuses_when_not_open(): void
    {
        $checkout = $this->openCheckout();
        $checkout->cancel();

        $this->expectException(CheckoutNotOpen::class);
        $checkout->addItem(SalesCheckoutItem::create(1, null, 1, new Money(1000)));
    }

    public function test_mark_paid_transitions_status_to_paid(): void
    {
        $checkout = $this->openCheckout();
        $checkout->markPaid(new DateTimeImmutable('now'));

        $this->assertSame('paid', $checkout->status());
    }

    public function test_mark_paid_refuses_once_the_reservation_has_expired(): void
    {
        $checkout = $this->openCheckout(new DateTimeImmutable('-1 minute'));

        $this->expectException(CheckoutReservationExpired::class);
        $checkout->markPaid(new DateTimeImmutable('now'));
    }

    public function test_mark_expired_transitions_status_to_expired(): void
    {
        $checkout = $this->openCheckout();
        $checkout->markExpired();

        $this->assertSame('expired', $checkout->status());
    }

    public function test_a_paid_checkout_cannot_be_expired(): void
    {
        $checkout = $this->openCheckout();
        $checkout->markPaid(new DateTimeImmutable('now'));

        $this->expectException(CheckoutNotOpen::class);
        $checkout->markExpired();
    }
}
