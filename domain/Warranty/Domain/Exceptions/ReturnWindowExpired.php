<?php

declare(strict_types=1);

namespace Domain\Warranty\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/** WAR-BR-12: "Requests made after this window shall be denied by default, subject to authorized administrative override." */
final class ReturnWindowExpired extends DomainException
{
    public static function forSale(int $saleId): self
    {
        return new self("The return window for sale [{$saleId}] has already expired.");
    }
}
