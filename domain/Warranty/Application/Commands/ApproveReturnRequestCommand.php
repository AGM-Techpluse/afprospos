<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Commands;

final readonly class ApproveReturnRequestCommand
{
    public function __construct(
        public int $returnRequestId,
        public int $approvedByStaffId,
    ) {}
}
