<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

/** Manager authorization records the quote terms and starts the repair. */
final readonly class AuthorizeRepairCommand
{
    public function __construct(
        public int $repairJobId,
        public int $downPaymentRequiredMinor,
        public int $authorizedByStaffId,
        /** The quote the customer agreed to pay, entered by the manager in the same action. */
        public int $repairCostMinor = 0,
        /** Manager may adjust the diagnosis fee while setting the quote. */
        public ?int $diagnosisFeeMinor = null,
        public ?string $specialAgreementNote = null,
        /** @var 'cash'|'pos_terminal'|'bank_transfer'|null */
        public ?string $downPaymentMethod = null,
    ) {}
}
