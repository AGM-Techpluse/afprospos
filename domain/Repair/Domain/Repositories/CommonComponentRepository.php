<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Repositories;

use Domain\Repair\Domain\Entities\CommonComponent;
use Domain\Repair\Domain\ValueObjects\CommonComponentId;

interface CommonComponentRepository
{
    public function get(CommonComponentId $id): CommonComponent;

    public function save(CommonComponent $component): CommonComponentId;

    public function delete(CommonComponentId $id): void;
}
