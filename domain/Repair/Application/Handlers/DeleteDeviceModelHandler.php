<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\DeleteDeviceModelCommand;
use Domain\Repair\Domain\Repositories\DeviceModelRepository;
use Domain\Repair\Domain\ValueObjects\DeviceModelId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class DeleteDeviceModelHandler
{
    public function __construct(
        private readonly DeviceModelRepository $deviceModels,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(DeleteDeviceModelCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new DeviceModelId($command->deviceModelId);
            $model = $this->deviceModels->get($id);

            $this->deviceModels->delete($id);

            $this->audit->record(
                module: 'Repair',
                eventType: 'DeviceModelDeleted',
                actorStaffId: new StaffId($command->deletedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'device_model',
                subjectId: $id->value,
                beforeState: ['name' => $model->name()],
                afterState: null,
            );
        });
    }
}
