<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

/** Unlike DeviceBrand/DeviceProblemTag's immutable parent scope, a model's brand stays editable — preserves the pre-existing bare-CRUD controller's behavior of letting staff correct a model filed under the wrong brand. */
final readonly class UpdateDeviceModelCommand
{
    public function __construct(
        public int $deviceModelId,
        public int $deviceBrandId,
        public string $name,
        public int $sortOrder,
        public int $updatedByStaffId,
    ) {}
}
