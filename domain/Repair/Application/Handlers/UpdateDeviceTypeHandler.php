<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\UpdateDeviceTypeCommand;
use Domain\Repair\Domain\Repositories\DeviceTypeRepository;
use Domain\Repair\Domain\ValueObjects\DeviceTypeId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class UpdateDeviceTypeHandler
{
    public function __construct(
        private readonly DeviceTypeRepository $deviceTypes,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(UpdateDeviceTypeCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new DeviceTypeId($command->deviceTypeId);
            $deviceType = $this->deviceTypes->get($id);
            $before = ['label' => $deviceType->label(), 'icon' => $deviceType->icon()];

            $deviceType->rename($command->label, $command->icon);
            $deviceType->reorder($command->sortOrder);
            $this->deviceTypes->save($deviceType);

            $this->audit->record(
                module: 'Repair',
                eventType: 'DeviceTypeUpdated',
                actorStaffId: new StaffId($command->updatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'device_type',
                subjectId: $id->value,
                beforeState: $before,
                afterState: ['label' => $command->label, 'icon' => $command->icon],
            );
        });
    }
}
