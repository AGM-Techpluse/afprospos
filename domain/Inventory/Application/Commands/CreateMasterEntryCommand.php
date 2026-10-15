<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Commands;

final readonly class CreateMasterEntryCommand
{
    public function __construct(
        public string $kind,
        public string $name,
        public ?string $contactPhone,
        public ?string $contactEmail,
        public int $createdByStaffId,
    ) {}
}
