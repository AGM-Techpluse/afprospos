<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\CreateDeviceBrandCommand;
use Domain\Repair\Domain\Entities\DeviceBrand;
use Domain\Repair\Domain\Repositories\DeviceBrandRepository;
use Domain\Repair\Domain\ValueObjects\DeviceTypeId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class CreateDeviceBrandHandler
{
    public function __construct(
        private readonly DeviceBrandRepository $deviceBrands,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(CreateDeviceBrandCommand $command): int
    {
        return $this->atomic->run(function () use ($command): int {
            $brand = DeviceBrand::create(new DeviceTypeId($command->deviceTypeId), $command->name, $command->sortOrder);
            $id = $this->deviceBrands->save($brand);

            $this->audit->record(
                module: 'Repair',
                eventType: 'DeviceBrandCreated',
                actorStaffId: new StaffId($command->createdByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'device_brand',
                subjectId: $id->value,
                beforeState: null,
                afterState: ['device_type_id' => $command->deviceTypeId, 'name' => $command->name],
            );

            return $id->value;
        });
    }
}
