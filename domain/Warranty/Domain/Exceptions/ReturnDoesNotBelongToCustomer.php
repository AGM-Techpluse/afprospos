<?php

declare(strict_types=1);

namespace Domain\Warranty\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class ReturnDoesNotBelongToCustomer extends DomainException
{
    public static function forSale(int $saleId): self
    {
        return new self("Sale [{$saleId}] does not belong to this customer.");
    }
}
