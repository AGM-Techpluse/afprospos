<?php

declare(strict_types=1);

namespace Domain\Warranty\Domain\Repositories;

use Domain\Warranty\Domain\Entities\WarrantyPolicy;
use Domain\Warranty\Domain\ValueObjects\WarrantyPolicyId;

interface WarrantyPolicyRepository
{
    public function get(WarrantyPolicyId $id): WarrantyPolicy;

    public function save(WarrantyPolicy $policy): WarrantyPolicyId;

    public function delete(WarrantyPolicyId $id): void;
}
