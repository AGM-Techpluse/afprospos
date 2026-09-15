<?php

declare(strict_types=1);

namespace Domain\Collection\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class InvalidCollectionTransition extends DomainException
{
    public static function forCase(int $collectionCaseId, string $from, string $to): self
    {
        return new self("Collection case [{$collectionCaseId}] cannot transition from [{$from}] to [{$to}].");
    }
}
