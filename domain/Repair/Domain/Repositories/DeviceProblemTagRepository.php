<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Repositories;

use Domain\Repair\Domain\Entities\DeviceProblemTag;
use Domain\Repair\Domain\ValueObjects\DeviceProblemTagId;

interface DeviceProblemTagRepository
{
    public function get(DeviceProblemTagId $id): DeviceProblemTag;

    public function save(DeviceProblemTag $tag): DeviceProblemTagId;

    public function delete(DeviceProblemTagId $id): void;
}
