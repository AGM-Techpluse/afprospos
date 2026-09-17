<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Commands;

final readonly class DeleteWarrantyPolicyCommand
{
    public function __construct(
        public int $warrantyPolicyId,
        public int $deletedByStaffId,
    ) {}
}
