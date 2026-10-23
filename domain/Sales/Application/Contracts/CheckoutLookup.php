<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Contracts;

/**
 * The published cross-module read contract for resolving who a checkout
 * belongs to — e.g. Payments' reconciliation queue, which only knows a
 * `sales_checkout` payable's id and otherwise has no way to show which
 * customer a payment is for (mirrors SaleLookup's role for a completed
 * Sale, CPNC §4.2).
 */
interface CheckoutLookup
{
    /**
     * @return array{
     *     id: int,
     *     shop_id: int,
     *     customer_id: int|null,
     *     walk_in_name: string|null,
     * }|null
     */
    public function find(int $checkoutId): ?array;
}
