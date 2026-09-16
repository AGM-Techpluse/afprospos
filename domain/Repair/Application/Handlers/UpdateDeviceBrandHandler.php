<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\UpdateDeviceBrandCommand;
use Domain\Repair\Domain\Repositories\DeviceBrandRepository;
use Domain\Repair\Domain\ValueObjects\DeviceBrandId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class UpdateDeviceBrandHandler
{
    public function __construct(
        private readonly DeviceBrandRepository $deviceBrands,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(UpdateDeviceBrandCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new DeviceBrandId($command->deviceBrandId);
            $brand = $this->deviceBrands->get($id);
            $before = ['name' => $brand->name()];

            $brand->rename($command->name);
            $brand->reorder($command->sortOrder);
            $this->deviceBrands->save($brand);

            $this->audit->record(
                module: 'Repair',
                eventType: 'DeviceBrandUpdated',
                actorStaffId: new StaffId($command->updatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'device_brand',
                subjectId: $id->value,
                beforeState: $before,
                afterState: ['name' => $command->name],
            );
        });
    }
}
