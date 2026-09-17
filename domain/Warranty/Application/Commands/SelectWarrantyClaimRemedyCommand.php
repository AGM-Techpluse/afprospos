<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Commands;

final readonly class SelectWarrantyClaimRemedyCommand
{
    /** @param  'repair'|'replace'|'refund'  $remedy  `exchange` is rejected by the Handler — see UnsupportedWarrantyRemedy. */
    public function __construct(
        public int $warrantyClaimId,
        public string $remedy,
        public int $selectedByStaffId,
    ) {}
}
