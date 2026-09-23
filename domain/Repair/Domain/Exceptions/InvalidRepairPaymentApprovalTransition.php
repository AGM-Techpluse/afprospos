<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class InvalidRepairPaymentApprovalTransition extends DomainException
{
    public static function forApproval(int $repairPaymentApprovalId, string $status): self
    {
        return new self("Repair payment approval [{$repairPaymentApprovalId}] is already [{$status}] and cannot be decided again.");
    }
}
