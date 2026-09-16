<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class DetachSuggestedPartCommand
{
    public function __construct(
        public int $suggestedPartId,
        public int $detachedByStaffId,
    ) {}
}
