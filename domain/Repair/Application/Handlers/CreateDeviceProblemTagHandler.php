<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\CreateDeviceProblemTagCommand;
use Domain\Repair\Domain\Entities\DeviceProblemTag;
use Domain\Repair\Domain\Repositories\DeviceBrandRepository;
use Domain\Repair\Domain\Repositories\DeviceModelRepository;
use Domain\Repair\Domain\Repositories\DeviceProblemTagRepository;
use Domain\Repair\Domain\ValueObjects\DeviceModelId;
use Domain\Repair\Domain\ValueObjects\DeviceTypeId;
use Domain\Shared\Domain\ValueObjects\StaffId;
use InvalidArgumentException;

final class CreateDeviceProblemTagHandler
{
    public function __construct(
        private readonly DeviceProblemTagRepository $problemTags,
        private readonly DeviceModelRepository $deviceModels,
        private readonly DeviceBrandRepository $deviceBrands,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(CreateDeviceProblemTagCommand $command): int
    {
        return $this->atomic->run(function () use ($command): int {
            $deviceModelId = $command->deviceModelId !== null ? new DeviceModelId($command->deviceModelId) : null;
            $deviceTypeId = $deviceModelId !== null
                ? $this->resolveDeviceTypeIdForModel($deviceModelId)
                : $this->requireDeviceTypeId($command->deviceTypeId);

            $tag = DeviceProblemTag::create($deviceTypeId, $deviceModelId, $command->label, $command->sortOrder, $command->commissionAmountMinor);
            $id = $this->problemTags->save($tag);

            $this->audit->record(
                module: 'Repair',
                eventType: 'DeviceProblemTagCreated',
                actorStaffId: new StaffId($command->createdByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'device_problem_tag',
                subjectId: $id->value,
                beforeState: null,
                afterState: [
                    'device_type_id' => $deviceTypeId->value,
                    'device_model_id' => $deviceModelId?->value,
                    'label' => $command->label,
                    'commission_amount_minor' => $command->commissionAmountMinor,
                ],
            );

            return $id->value;
        });
    }

    /** A model-specific tag's type is always derived from the model itself — never trusted from the client — so it can never end up mismatched. */
    private function resolveDeviceTypeIdForModel(DeviceModelId $deviceModelId): DeviceTypeId
    {
        $model = $this->deviceModels->get($deviceModelId);
        $brand = $this->deviceBrands->get($model->deviceBrandId());

        return $brand->deviceTypeId();
    }

    private function requireDeviceTypeId(?int $deviceTypeId): DeviceTypeId
    {
        if ($deviceTypeId === null) {
            throw new InvalidArgumentException('Either deviceTypeId or deviceModelId must be given.');
        }

        return new DeviceTypeId($deviceTypeId);
    }
}
