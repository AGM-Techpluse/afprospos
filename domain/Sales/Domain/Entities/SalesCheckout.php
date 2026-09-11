<?php

declare(strict_types=1);

namespace Domain\Sales\Domain\Entities;

use DateTimeImmutable;
use Domain\Sales\Domain\Exceptions\CheckoutNotOpen;
use Domain\Sales\Domain\Exceptions\CheckoutReservationExpired;
use Domain\Sales\Domain\Policies\CheckoutReservationPolicy;
use Domain\Sales\Domain\Services\CheckoutPricingService;
use Domain\Sales\Domain\ValueObjects\CheckoutId;
use Domain\Sales\Domain\ValueObjects\SalesCheckoutItemId;
use Domain\Shared\Domain\ValueObjects\CustomerId;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;
use InvalidArgumentException;

/**
 * A mutable, in-progress cart holding an inventory reservation while
 * payment is pending (DBDD §13.1, BLD §4.1). Ordinary cart mutations
 * (add/remove item, apply adjustment, cancel) only ever check
 * `status === 'open'` — expiry-by-clock is the async worker's job
 * (SALE/INV-BR-06: "the system shall expire the checkout"), not
 * something every mutation re-derives. `markPaid()` is the one
 * exception: it re-checks the clock as defense-in-depth against the
 * worker racing a payment confirmation (SALE/PAY-BR-08).
 */
final class SalesCheckout
{
    /** @var SalesCheckoutItem[] */
    private array $items;

    /** @var SalesCheckoutAdjustment[] */
    private array $adjustments;

    private function __construct(
        private readonly ?CheckoutId $id,
        private readonly ShopId $shopId,
        private readonly ?CustomerId $customerId,
        private readonly StaffId $cashierStaffId,
        private string $status,
        private DateTimeImmutable $reservationExpiresAt,
        array $items,
        array $adjustments,
        private int $subtotalMinor,
        private int $discountMinor,
        private int $totalMinor,
    ) {
        $this->items = $items;
        $this->adjustments = $adjustments;
    }

    public static function open(ShopId $shopId, ?CustomerId $customerId, StaffId $cashierStaffId, DateTimeImmutable $reservationExpiresAt): self
    {
        return new self(null, $shopId, $customerId, $cashierStaffId, 'open', $reservationExpiresAt, [], [], 0, 0, 0);
    }

    /**
     * @param  SalesCheckoutItem[]  $items
     * @param  SalesCheckoutAdjustment[]  $adjustments
     */
    public static function reconstitute(
        CheckoutId $id,
        ShopId $shopId,
        ?CustomerId $customerId,
        StaffId $cashierStaffId,
        string $status,
        DateTimeImmutable $reservationExpiresAt,
        array $items,
        array $adjustments,
        int $subtotalMinor,
        int $discountMinor,
        int $totalMinor,
    ): self {
        return new self($id, $shopId, $customerId, $cashierStaffId, $status, $reservationExpiresAt, $items, $adjustments, $subtotalMinor, $discountMinor, $totalMinor);
    }

    public function addItem(SalesCheckoutItem $item): void
    {
        $this->assertOpen();
        $this->items[] = $item;
        $this->recompute();
    }

    /** Removes and returns the item so the caller can release its Inventory reservation. */
    public function removeItem(SalesCheckoutItemId $itemId): SalesCheckoutItem
    {
        $this->assertOpen();

        foreach ($this->items as $index => $item) {
            if ($item->id()?->equals($itemId)) {
                unset($this->items[$index]);
                $this->items = array_values($this->items);
                $this->recompute();

                return $item;
            }
        }

        throw new InvalidArgumentException("Checkout item [{$itemId->value}] is not on this checkout.");
    }

    /** MKT-BR-06: non-stackable by default — a new adjustment replaces any existing one. */
    public function applyAdjustment(SalesCheckoutAdjustment $adjustment): void
    {
        $this->assertOpen();
        $this->adjustments = [$adjustment];
        $this->recompute();
    }

    public function cancel(): void
    {
        $this->assertOpen();
        $this->status = 'cancelled';
    }

    /** Used only by the expiry worker, which already verifies status='open' via its own query before calling this. */
    public function markExpired(): void
    {
        $this->assertOpen();
        $this->status = 'expired';
    }

    /** SALE/PAY-BR-08: defense-in-depth clock check against the expiry worker racing this call. */
    public function markPaid(DateTimeImmutable $now): void
    {
        $this->assertOpen();

        if (! (new CheckoutReservationPolicy)->isMutable($this->status, $this->reservationExpiresAt, $now)) {
            throw CheckoutReservationExpired::forCheckout($this->id?->value ?? 0);
        }

        $this->status = 'paid';
    }

    private function assertOpen(): void
    {
        if ($this->status !== 'open') {
            throw CheckoutNotOpen::forCheckout($this->id?->value ?? 0, $this->status);
        }
    }

    private function recompute(): void
    {
        $totals = CheckoutPricingService::recompute($this->items, $this->adjustments);
        $this->subtotalMinor = $totals['subtotalMinor'];
        $this->discountMinor = $totals['discountMinor'];
        $this->totalMinor = $totals['totalMinor'];
    }

    public function id(): ?CheckoutId
    {
        return $this->id;
    }

    public function shopId(): ShopId
    {
        return $this->shopId;
    }

    public function customerId(): ?CustomerId
    {
        return $this->customerId;
    }

    public function cashierStaffId(): StaffId
    {
        return $this->cashierStaffId;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function reservationExpiresAt(): DateTimeImmutable
    {
        return $this->reservationExpiresAt;
    }

    /** @return SalesCheckoutItem[] */
    public function items(): array
    {
        return $this->items;
    }

    /** @return SalesCheckoutAdjustment[] */
    public function adjustments(): array
    {
        return $this->adjustments;
    }

    public function subtotalMinor(): int
    {
        return $this->subtotalMinor;
    }

    public function discountMinor(): int
    {
        return $this->discountMinor;
    }

    public function totalMinor(): int
    {
        return $this->totalMinor;
    }
}
