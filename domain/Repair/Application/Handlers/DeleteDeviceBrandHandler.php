<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\DeleteDeviceBrandCommand;
use Domain\Repair\Domain\Repositories\DeviceBrandRepository;
use Domain\Repair\Domain\ValueObjects\DeviceBrandId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class DeleteDeviceBrandHandler
{
    public function __construct(
        private readonly DeviceBrandRepository $deviceBrands,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(DeleteDeviceBrandCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new DeviceBrandId($command->deviceBrandId);
            $brand = $this->deviceBrands->get($id);

            $this->deviceBrands->delete($id);

            $this->audit->record(
                module: 'Repair',
                eventType: 'DeviceBrandDeleted',
                actorStaffId: new StaffId($command->deletedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'device_brand',
                subjectId: $id->value,
                beforeState: ['name' => $brand->name()],
                afterState: null,
            );
        });
    }
}
