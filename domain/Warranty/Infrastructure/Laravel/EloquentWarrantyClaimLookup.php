<?php

declare(strict_types=1);

namespace Domain\Warranty\Infrastructure\Laravel;

use Domain\Warranty\Application\Contracts\WarrantyClaimLookup;
use Domain\Warranty\Infrastructure\Persistence\Eloquent\WarrantyClaimRecord;

/** The Contract's implementation — Eloquent is permitted here (Infrastructure query layer, CPNC §2.1). */
final class EloquentWarrantyClaimLookup implements WarrantyClaimLookup
{
    public function find(int $warrantyClaimId): ?array
    {
        $claim = WarrantyClaimRecord::query()->find($warrantyClaimId);

        if ($claim === null) {
            return null;
        }

        return [
            'id' => $claim->id,
            'customer_id' => $claim->customer_id,
            'resolution_state' => $claim->resolution_state,
        ];
    }
}
