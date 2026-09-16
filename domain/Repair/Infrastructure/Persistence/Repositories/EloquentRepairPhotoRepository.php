<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Repositories;

use Domain\Repair\Domain\Entities\RepairPhoto;
use Domain\Repair\Domain\Repositories\RepairPhotoRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Repair\Domain\ValueObjects\RepairPhotoId;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobPhotoRecord;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class EloquentRepairPhotoRepository implements RepairPhotoRepository
{
    public function get(RepairPhotoId $id): RepairPhoto
    {
        $record = RepairJobPhotoRecord::query()->findOrFail($id->value);

        return RepairPhoto::reconstitute(
            new RepairPhotoId($record->id),
            new RepairJobId($record->repair_job_id),
            $record->path,
            $record->caption,
            new StaffId($record->uploaded_by_staff_id),
        );
    }

    /** Photos are immutable once uploaded (Entity has no mutators) -- `save()` only ever inserts. */
    public function save(RepairPhoto $photo): RepairPhotoId
    {
        $record = RepairJobPhotoRecord::query()->create([
            'repair_job_id' => $photo->repairJobId()->value,
            'path' => $photo->path(),
            'caption' => $photo->caption(),
            'uploaded_by_staff_id' => $photo->uploadedByStaffId()->value,
        ]);

        return new RepairPhotoId($record->id);
    }

    public function delete(RepairPhotoId $id): void
    {
        RepairJobPhotoRecord::query()->whereKey($id->value)->delete();
    }
}
