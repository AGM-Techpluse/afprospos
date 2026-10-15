<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class DecideDiagnosisFeeCommand
{
    public function __construct(
        public int $repairJobId,
        public bool $approve,
        public int $decidedByStaffId,
    ) {}
}
