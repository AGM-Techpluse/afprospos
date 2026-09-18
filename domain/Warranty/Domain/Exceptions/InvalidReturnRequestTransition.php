<?php

declare(strict_types=1);

namespace Domain\Warranty\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class InvalidReturnRequestTransition extends DomainException
{
    public static function forReturnRequest(int $returnRequestId, string $from, string $to): self
    {
        return new self("Return request [{$returnRequestId}] cannot transition from [{$from}] to [{$to}].");
    }
}
