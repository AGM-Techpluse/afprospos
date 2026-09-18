<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Commands;

final readonly class DenyReturnRequestCommand
{
    public function __construct(
        public int $returnRequestId,
        public int $deniedByStaffId,
        public string $reason,
    ) {}
}
