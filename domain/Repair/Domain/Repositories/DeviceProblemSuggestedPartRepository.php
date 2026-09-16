<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Repositories;

use Domain\Repair\Domain\ValueObjects\DeviceProblemTagId;

/**
 * A plain association (problem tag <-> Inventory `sku_id`, a
 * cross-module reference by value only per CPNC §0.2) with no behavior
 * of its own, so it's managed directly through this repository rather
 * than wrapped in its own rich entity.
 */
interface DeviceProblemSuggestedPartRepository
{
    public function attach(DeviceProblemTagId $problemTagId, int $skuId): int;

    public function detach(int $id): void;

    /** @return array<int, array{id: int, sku_id: int}> */
    public function forProblemTag(DeviceProblemTagId $problemTagId): array;
}
