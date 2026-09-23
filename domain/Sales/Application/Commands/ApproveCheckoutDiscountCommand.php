<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Commands;

/** MKT-BR-15: approves the checkout's current (only) discount/adjustment. */
final readonly class ApproveCheckoutDiscountCommand
{
    public function __construct(
        public int $checkoutId,
        public int $approvedByStaffId,
    ) {}
}
