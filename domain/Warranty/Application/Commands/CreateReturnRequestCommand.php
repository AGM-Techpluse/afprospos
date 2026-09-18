<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Commands;

final readonly class CreateReturnRequestCommand
{
    public function __construct(
        public int $saleId,
        public int $customerId,
        public ?int $submittedByStaffId,
    ) {}
}
