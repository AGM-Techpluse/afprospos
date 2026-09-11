<?php

declare(strict_types=1);

namespace Domain\Sales\Domain\Repositories;

use Domain\Sales\Domain\Entities\SalesCheckout;
use Domain\Sales\Domain\ValueObjects\CheckoutId;

interface SalesCheckoutRepository
{
    public function get(CheckoutId $id): SalesCheckout;

    /** Locks the checkout row with SELECT ... FOR UPDATE — the serialization point for expiry racing payment confirmation (SALE/PAY-BR-08). */
    public function lockForUpdate(CheckoutId $id): SalesCheckout;

    public function save(SalesCheckout $checkout): CheckoutId;

    /**
     * Unlocked candidate read — open checkouts past their expiry, for
     * the expiry worker to attempt locking one at a time (DBDD §26).
     *
     * @return int[]
     */
    public function findExpirableIds(int $limit): array;
}
