<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\UploadRepairPhotoCommand;
use Domain\Repair\Domain\Entities\RepairPhoto;
use Domain\Repair\Domain\Repositories\RepairPhotoRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class UploadRepairPhotoHandler
{
    public function __construct(
        private readonly RepairPhotoRepository $photos,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(UploadRepairPhotoCommand $command): int
    {
        return $this->atomic->run(function () use ($command): int {
            $uploader = new StaffId($command->uploadedByStaffId);
            $photo = RepairPhoto::record(new RepairJobId($command->repairJobId), $command->path, $command->caption, $uploader);
            $id = $this->photos->save($photo);

            $this->audit->record(
                module: 'Repair',
                eventType: 'RepairPhotoUploaded',
                actorStaffId: $uploader,
                actorRoleSnapshot: null,
                subjectType: 'repair_job',
                subjectId: $command->repairJobId,
                beforeState: null,
                afterState: ['repair_photo_id' => $id->value, 'caption' => $command->caption],
            );

            return $id->value;
        });
    }
}
