<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Repositories;

use Domain\Repair\Domain\Entities\RepairPhoto;
use Domain\Repair\Domain\ValueObjects\RepairPhotoId;

interface RepairPhotoRepository
{
    public function get(RepairPhotoId $id): RepairPhoto;

    public function save(RepairPhoto $photo): RepairPhotoId;

    public function delete(RepairPhotoId $id): void;
}
