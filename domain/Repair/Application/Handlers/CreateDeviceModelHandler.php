<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\CreateDeviceModelCommand;
use Domain\Repair\Domain\Entities\DeviceModel;
use Domain\Repair\Domain\Repositories\DeviceModelRepository;
use Domain\Repair\Domain\ValueObjects\DeviceBrandId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class CreateDeviceModelHandler
{
    public function __construct(
        private readonly DeviceModelRepository $deviceModels,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(CreateDeviceModelCommand $command): int
    {
        return $this->atomic->run(function () use ($command): int {
            $deviceBrandId = new DeviceBrandId($command->deviceBrandId);
            $existing = $this->deviceModels->findByBrandAndName($deviceBrandId, $command->name);

            if ($existing !== null) {
                return $existing->id()->value;
            }

            $model = DeviceModel::create($deviceBrandId, $command->name, $command->sortOrder);
            $id = $this->deviceModels->save($model);

            $this->audit->record(
                module: 'Repair',
                eventType: 'DeviceModelCreated',
                actorStaffId: new StaffId($command->createdByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'device_model',
                subjectId: $id->value,
                beforeState: null,
                afterState: ['device_brand_id' => $command->deviceBrandId, 'name' => $command->name],
            );

            return $id->value;
        });
    }
}
