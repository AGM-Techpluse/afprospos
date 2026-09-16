<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\CreateDeviceTypeCommand;
use Domain\Repair\Domain\Entities\DeviceType;
use Domain\Repair\Domain\Repositories\DeviceTypeRepository;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class CreateDeviceTypeHandler
{
    public function __construct(
        private readonly DeviceTypeRepository $deviceTypes,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(CreateDeviceTypeCommand $command): int
    {
        return $this->atomic->run(function () use ($command): int {
            $deviceType = DeviceType::create($command->label, $command->icon, $command->sortOrder);
            $id = $this->deviceTypes->save($deviceType);

            $this->audit->record(
                module: 'Repair',
                eventType: 'DeviceTypeCreated',
                actorStaffId: new StaffId($command->createdByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'device_type',
                subjectId: $id->value,
                beforeState: null,
                afterState: ['label' => $command->label, 'icon' => $command->icon],
            );

            return $id->value;
        });
    }
}
