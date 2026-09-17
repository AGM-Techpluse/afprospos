<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Commands;

final readonly class AssessClaimCommand
{
    public function __construct(
        public int $warrantyClaimId,
        public bool $eligible,
        public ?string $notes,
        public int $assessedByStaffId,
    ) {}
}
