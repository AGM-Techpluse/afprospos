<?php

declare(strict_types=1);

namespace Domain\Warranty\Domain\Repositories;

use Domain\Warranty\Domain\Entities\WarrantyClaim;
use Domain\Warranty\Domain\ValueObjects\WarrantyClaimId;

interface WarrantyClaimRepository
{
    public function get(WarrantyClaimId $id): WarrantyClaim;

    /** Locks the claim row with SELECT ... FOR UPDATE — the serialization point for a duplicate assess/resolve race. */
    public function lockForUpdate(WarrantyClaimId $id): WarrantyClaim;

    public function save(WarrantyClaim $claim): WarrantyClaimId;
}
