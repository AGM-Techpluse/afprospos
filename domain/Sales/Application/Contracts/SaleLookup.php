<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Contracts;

/**
 * The published cross-module read contract for validating a warranty
 * claim's source sale (WAR-BR-02/03: a claim must trace back to a real
 * sale, and eligibility depends on when it happened) — Warranty depends
 * on this, and only this, never on Sales' Domain/Infrastructure
 * internals directly (CPNC §4.2, same shape as CollectionCaseLookup).
 */
interface SaleLookup
{
    /**
     * @return array{
     *     id: int,
     *     shop_id: int,
     *     customer_id: int|null,
     *     total_minor: int,
     *     invoice_number: string|null,
     *     payment_transaction_id: int|null,
     *     created_at: string,
     *     items: array<int, array{sku_id: int, inventory_item_id: int|null}>
     * }|null
     */
    public function find(int $saleId): ?array;
}
