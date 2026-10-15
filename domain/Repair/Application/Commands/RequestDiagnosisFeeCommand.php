<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

/**
 * The diagnosis fee is its own payment, separate from the repair quote: a customer may
 * decline the repair after diagnosis and still owe (or not owe) the fee. Never added to
 * the repair cost or the down payment.
 */
final readonly class RequestDiagnosisFeeCommand
{
    public function __construct(
        public int $repairJobId,
        public string $method,
        public int $requestedByStaffId,
    ) {}
}
