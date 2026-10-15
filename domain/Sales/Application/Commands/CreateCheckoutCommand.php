<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Commands;

final readonly class CreateCheckoutCommand
{
    public function __construct(
        public int $shopId,
        public ?int $customerId,
        public int $cashierStaffId,
        public ?string $walkInName = null,
        public ?string $walkInPhone = null,
        public ?string $walkInEmail = null,
    ) {}
}
