<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class DeleteCommonComponentCommand
{
    public function __construct(
        public int $commonComponentId,
        public int $deletedByStaffId,
    ) {}
}
