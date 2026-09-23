<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

/**
 * `downPaymentRequiredMinor = 0` grants authorization immediately; a
 * positive amount only records the requirement — frontdesk then requests
 * (`RequestRepairDownPaymentApprovalCommand`) once the customer has
 * actually paid, and Manager approval is what creates the real payable.
 */
final readonly class AuthorizeRepairCommand
{
    public function __construct(
        public int $repairJobId,
        public int $downPaymentRequiredMinor,
        public int $authorizedByStaffId,
    ) {}
}
