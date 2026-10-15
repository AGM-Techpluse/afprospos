<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Commands;

final readonly class DeleteMasterEntryCommand
{
    public function __construct(
        public int $entryId,
        public int $deletedByStaffId,
    ) {}
}
