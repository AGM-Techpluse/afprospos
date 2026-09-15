<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

/** `downPaymentRequiredMinor = 0` grants authorization immediately; a positive amount creates a payable via PaymentInitiationService and the job waits for confirmation. */
final readonly class AuthorizeRepairCommand
{
    public function __construct(
        public int $repairJobId,
        public int $downPaymentRequiredMinor,
        public string $downPaymentMethod,
        public int $authorizedByStaffId,
    ) {}
}
