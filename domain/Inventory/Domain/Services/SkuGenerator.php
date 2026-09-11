<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Services;

/**
 * Pure formatting of `[ShopCode]-[CategoryCode]-[Sequence]` (DBDD
 * §14.2, e.g. "LGS-PHN-000482"). Deliberately takes the next sequence
 * number as a plain argument rather than querying for it itself — the
 * Repository/Handler owns "what is the next sequence", keeping this
 * class framework-free and trivially testable.
 */
final class SkuGenerator
{
    public static function generate(string $shopCode, string $category, int $sequence): string
    {
        $categoryCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $category) ?? '', 0, 3));

        return sprintf('%s-%s-%06d', strtoupper($shopCode), $categoryCode, $sequence);
    }
}
