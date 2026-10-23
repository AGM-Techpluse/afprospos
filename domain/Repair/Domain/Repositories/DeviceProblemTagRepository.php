<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Repositories;

use Domain\Repair\Domain\Entities\DeviceProblemTag;
use Domain\Repair\Domain\ValueObjects\DeviceModelId;
use Domain\Repair\Domain\ValueObjects\DeviceProblemTagId;
use Domain\Repair\Domain\ValueObjects\DeviceTypeId;

interface DeviceProblemTagRepository
{
    public function get(DeviceProblemTagId $id): DeviceProblemTag;

    public function save(DeviceProblemTag $tag): DeviceProblemTagId;

    public function delete(DeviceProblemTagId $id): void;

    /** Backs bulk-import idempotency — a repeat import must reuse an existing tag by (type, model-or-null, label), not create a duplicate. */
    public function findByScopeAndLabel(DeviceTypeId $deviceTypeId, ?DeviceModelId $deviceModelId, string $label): ?DeviceProblemTag;
}
