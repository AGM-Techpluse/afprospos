<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

/** `path` is already-stored (Controller-level concern, CPNC §2.3 -- same pattern as ImportProductsCommand carrying parsed rows rather than a raw upload). */
final readonly class UploadRepairPhotoCommand
{
    public function __construct(
        public int $repairJobId,
        public string $path,
        public ?string $caption,
        public int $uploadedByStaffId,
    ) {}
}
