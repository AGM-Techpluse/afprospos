<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Entities;

use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Repair\Domain\ValueObjects\RepairPhotoId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/** Immutable once uploaded (no mutators) -- matches RepairDiagnosis's convention: no `updated_at` signals append-only, evidence isn't edited, only removed. */
final class RepairPhoto
{
    private function __construct(
        private readonly ?RepairPhotoId $id,
        private readonly RepairJobId $repairJobId,
        private readonly string $path,
        private readonly ?string $caption,
        private readonly StaffId $uploadedByStaffId,
    ) {}

    public static function record(RepairJobId $repairJobId, string $path, ?string $caption, StaffId $uploadedByStaffId): self
    {
        return new self(null, $repairJobId, $path, $caption, $uploadedByStaffId);
    }

    public static function reconstitute(RepairPhotoId $id, RepairJobId $repairJobId, string $path, ?string $caption, StaffId $uploadedByStaffId): self
    {
        return new self($id, $repairJobId, $path, $caption, $uploadedByStaffId);
    }

    public function id(): ?RepairPhotoId
    {
        return $this->id;
    }

    public function repairJobId(): RepairJobId
    {
        return $this->repairJobId;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function caption(): ?string
    {
        return $this->caption;
    }

    public function uploadedByStaffId(): StaffId
    {
        return $this->uploadedByStaffId;
    }
}
