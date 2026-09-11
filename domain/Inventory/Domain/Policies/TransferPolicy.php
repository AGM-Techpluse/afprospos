<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Policies;

/**
 * INV-BR-14: inventory that is reserved or otherwise unavailable
 * cannot be transferred between shops.
 */
final class TransferPolicy
{
    public function canTransferItem(string $itemStatus): bool
    {
        return $itemStatus === 'available';
    }

    public function canTransferQuantity(int $onHand, int $reserved, int $requestedQuantity): bool
    {
        return (new ReservationPolicy)->canReserve($onHand, $reserved, $requestedQuantity);
    }
}
