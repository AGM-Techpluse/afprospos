<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Commands;

final readonly class RenameMasterEntryCommand
{
    public function __construct(
        public int $entryId,
        public string $name,
        public int $renamedByStaffId,
    ) {}
}
