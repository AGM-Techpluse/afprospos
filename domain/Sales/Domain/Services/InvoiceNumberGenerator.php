<?php

declare(strict_types=1);

namespace Domain\Sales\Domain\Services;

/**
 * Pure formatting of `INV-[ShopCode]-[Sequence]`, mirroring
 * Inventory\Domain\Services\SkuGenerator's exact style. Deliberately
 * takes the next sequence number as a plain argument rather than
 * querying for it itself, keeping this class framework-free.
 */
final class InvoiceNumberGenerator
{
    public static function generate(string $shopCode, int $sequence): string
    {
        return sprintf('INV-%s-%06d', strtoupper($shopCode), $sequence);
    }
}
