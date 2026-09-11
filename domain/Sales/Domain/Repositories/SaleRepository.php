<?php

declare(strict_types=1);

namespace Domain\Sales\Domain\Repositories;

use Domain\Sales\Domain\Entities\Sale;
use Domain\Sales\Domain\ValueObjects\SaleId;

interface SaleRepository
{
    public function get(SaleId $id): Sale;

    public function save(Sale $sale): SaleId;

    /** Sequence for InvoiceNumberGenerator — per-shop running count, matching SkuRepository's next-sequence pattern. */
    public function nextInvoiceSequence(int $shopId): int;
}
