<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\DeleteDeviceTypeCommand;
use Domain\Repair\Domain\Repositories\DeviceTypeRepository;
use Domain\Repair\Domain\ValueObjects\DeviceTypeId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/** Deleting a device type cascades to its brands and problem tags at the DB level (migration's cascadeOnDelete) — repair jobs that already reference a now-deleted problem tag keep their label_snapshot regardless. */
final class DeleteDeviceTypeHandler
{
    public function __construct(
        private readonly DeviceTypeRepository $deviceTypes,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(DeleteDeviceTypeCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new DeviceTypeId($command->deviceTypeId);
            $deviceType = $this->deviceTypes->get($id);

            $this->deviceTypes->delete($id);

            $this->audit->record(
                module: 'Repair',
                eventType: 'DeviceTypeDeleted',
                actorStaffId: new StaffId($command->deletedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'device_type',
                subjectId: $id->value,
                beforeState: ['label' => $deviceType->label()],
                afterState: null,
            );
        });
    }
}
