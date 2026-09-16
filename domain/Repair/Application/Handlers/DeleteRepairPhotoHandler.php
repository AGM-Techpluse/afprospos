<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\DeleteRepairPhotoCommand;
use Domain\Repair\Domain\Repositories\RepairPhotoRepository;
use Domain\Repair\Domain\ValueObjects\RepairPhotoId;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Illuminate\Support\Facades\Storage;

final class DeleteRepairPhotoHandler
{
    public function __construct(
        private readonly RepairPhotoRepository $photos,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(DeleteRepairPhotoCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new RepairPhotoId($command->photoId);
            $photo = $this->photos->get($id);

            $this->photos->delete($id);
            Storage::disk('public')->delete($photo->path());

            $this->audit->record(
                module: 'Repair',
                eventType: 'RepairPhotoDeleted',
                actorStaffId: new StaffId($command->deletedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'repair_job',
                subjectId: $photo->repairJobId()->value,
                beforeState: ['repair_photo_id' => $id->value],
                afterState: null,
            );
        });
    }
}
