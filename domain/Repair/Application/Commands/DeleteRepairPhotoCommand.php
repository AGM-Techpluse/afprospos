<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class DeleteRepairPhotoCommand
{
    public function __construct(
        public int $photoId,
        public int $deletedByStaffId,
    ) {}
}
