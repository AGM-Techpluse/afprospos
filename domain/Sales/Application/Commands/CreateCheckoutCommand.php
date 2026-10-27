<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Commands;

final readonly class CreateCheckoutCommand
{
    public function __construct(
        public int $shopId,
        public ?int $customerId,
        /** Null for a customer self-service cart (the storefront) — no cashier rang it up. */
        public ?int $cashierStaffId,
        public ?string $walkInName = null,
        public ?string $walkInPhone = null,
        public ?string $walkInEmail = null,
        /** Skips the find-and-reuse-the-cashier's-existing-open-checkout short-circuit — a deliberate second (third, ...) concurrent checkout, not a navigation resuming the first one. */
        public bool $forceNew = false,
    ) {}
}
