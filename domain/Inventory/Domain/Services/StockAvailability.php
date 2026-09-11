<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Services;

/**
 * The single place `on_hand - reserved` is computed (BLD §2.1, DBDD
 * §5.2/§15.4): Available is never persisted, so every caller — the
 * entity itself, a read-side Query, a reconciliation check — must
 * derive it from this one formula, not reimplement the subtraction.
 */
final class StockAvailability
{
    public static function available(int $onHand, int $reserved): int
    {
        return $onHand - $reserved;
    }
}
