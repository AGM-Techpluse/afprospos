<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\UpdateDeviceModelCommand;
use Domain\Repair\Domain\Repositories\DeviceModelRepository;
use Domain\Repair\Domain\ValueObjects\DeviceBrandId;
use Domain\Repair\Domain\ValueObjects\DeviceModelId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class UpdateDeviceModelHandler
{
    public function __construct(
        private readonly DeviceModelRepository $deviceModels,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(UpdateDeviceModelCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new DeviceModelId($command->deviceModelId);
            $model = $this->deviceModels->get($id);
            $before = ['device_brand_id' => $model->deviceBrandId()->value, 'name' => $model->name()];

            $model->reassignBrand(new DeviceBrandId($command->deviceBrandId));
            $model->rename($command->name);
            $model->reorder($command->sortOrder);
            $this->deviceModels->save($model);

            $this->audit->record(
                module: 'Repair',
                eventType: 'DeviceModelUpdated',
                actorStaffId: new StaffId($command->updatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'device_model',
                subjectId: $id->value,
                beforeState: $before,
                afterState: ['device_brand_id' => $command->deviceBrandId, 'name' => $command->name],
            );
        });
    }
}
