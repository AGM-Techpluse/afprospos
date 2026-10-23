<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use Domain\Repair\Application\Commands\AddFixedFaultToCatalogCommand;
use Domain\Repair\Application\Commands\CreateDeviceProblemTagCommand;
use Domain\Repair\Domain\Exceptions\RepairHasNoDeviceModel;
use Domain\Repair\Domain\Repositories\DeviceBrandRepository;
use Domain\Repair\Domain\Repositories\DeviceModelRepository;
use Domain\Repair\Domain\Repositories\DeviceProblemTagRepository;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;

/**
 * A technician-typed "newly discovered fixed fault" not already in the
 * catalog — joins it (scoped to this repair's own device model) with
 * the commission the technician set, so it's immediately selectable in
 * the device-specific fixed-faults picker too.
 */
final class AddFixedFaultToCatalogHandler
{
    public function __construct(
        private readonly RepairJobRepository $repairJobs,
        private readonly DeviceProblemTagRepository $problemTags,
        private readonly DeviceModelRepository $deviceModels,
        private readonly DeviceBrandRepository $deviceBrands,
        private readonly CreateDeviceProblemTagHandler $createProblemTag,
    ) {}

    /** @return array{id: int, label: string, commission_amount_minor: ?int} */
    public function handle(AddFixedFaultToCatalogCommand $command): array
    {
        $job = $this->repairJobs->get(new RepairJobId($command->repairJobId));
        $deviceModelId = $job->deviceModelId();

        if ($deviceModelId === null) {
            throw RepairHasNoDeviceModel::forJob($command->repairJobId);
        }

        $model = $this->deviceModels->get($deviceModelId);
        $brand = $this->deviceBrands->get($model->deviceBrandId());
        $deviceTypeId = $brand->deviceTypeId();

        $existing = $this->problemTags->findByScopeAndLabel($deviceTypeId, $deviceModelId, $command->label);

        if ($existing !== null) {
            return ['id' => $existing->id()->value, 'label' => $existing->label(), 'commission_amount_minor' => $existing->commissionAmountMinor()];
        }

        $tagId = $this->createProblemTag->handle(new CreateDeviceProblemTagCommand(
            deviceTypeId: null,
            deviceModelId: $deviceModelId->value,
            label: $command->label,
            sortOrder: 0,
            createdByStaffId: $command->addedByStaffId,
            commissionAmountMinor: $command->commissionAmountMinor,
        ));

        return ['id' => $tagId, 'label' => $command->label, 'commission_amount_minor' => $command->commissionAmountMinor];
    }
}
