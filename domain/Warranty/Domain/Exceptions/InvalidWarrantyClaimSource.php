<?php

declare(strict_types=1);

namespace Domain\Warranty\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class InvalidWarrantyClaimSource extends DomainException
{
    public static function forSale(int $saleId): self
    {
        return new self("Sale [{$saleId}] does not exist or does not belong to this customer.");
    }

    public static function forRepairJob(int $repairJobId): self
    {
        return new self("Repair job [{$repairJobId}] does not exist or does not belong to this customer.");
    }

    public static function missing(): self
    {
        return new self('A warranty claim must reference an originating sale or repair job.');
    }
}
