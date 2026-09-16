<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class AttachSuggestedPartCommand
{
    public function __construct(
        public int $deviceProblemTagId,
        public int $skuId,
        public int $attachedByStaffId,
    ) {}
}
