<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Policies;

use Domain\Inventory\Domain\Services\StockAvailability;

/**
 * INV-BR-15 / BLD §3.4: available-vs-requested is the one check every
 * allocation path (sale, repair, transfer, low-stock alert) must run
 * against — this is the single place that comparison lives, reused by
 * both the authoritative entity mutation and cheap read-side
 * availability queries (mirrors ShopScopePolicy vs.
 * ActorContext::canAccessShop()'s advisory/authoritative split).
 */
final class ReservationPolicy
{
    public function canReserve(int $onHand, int $reserved, int $requestedQuantity): bool
    {
        return StockAvailability::available($onHand, $reserved) >= $requestedQuantity;
    }
}
