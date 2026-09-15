<?php

declare(strict_types=1);

namespace Domain\Collection\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/** Implementation Plan Phase 6 exit criterion: "the device cannot be released when payment is outstanding without an authorized, audited override." */
final class ReleaseBlockedByOutstandingBalance extends DomainException
{
    public static function forCase(int $collectionCaseId): self
    {
        return new self("Collection case [{$collectionCaseId}] has an outstanding balance and no recorded administrative override — release is blocked.");
    }
}
