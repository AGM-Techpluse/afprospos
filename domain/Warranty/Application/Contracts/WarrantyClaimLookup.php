<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Contracts;

/**
 * The published cross-module read contract for a warranty claim's own
 * facts — added for Notifications' OnWarrantyClaimResolved listener,
 * which needs the claim's customer_id and WarrantyClaimResolved doesn't
 * carry one (mirrors Repair's RepairJobLookup, same shape/reasoning).
 * Purely additive: no existing Warranty Handler changes.
 */
interface WarrantyClaimLookup
{
    /**
     * @return array{id: int, customer_id: int, resolution_state: string}|null
     */
    public function find(int $warrantyClaimId): ?array;
}
