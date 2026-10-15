<?php

declare(strict_types=1);

namespace Domain\Collection\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class CollectionTransferNotAllowed extends DomainException
{
    public static function sameShop(int $shopId): self
    {
        return new self("Collection case is already assigned to shop [{$shopId}].");
    }

    public static function resolved(int $collectionCaseId): self
    {
        return new self("Collection case [{$collectionCaseId}] is resolved and cannot be transferred.");
    }

    public static function warehouse(int $shopId): self
    {
        return new self("Shop [{$shopId}] is a warehouse. Collection pickup must happen at a customer-facing shop.");
    }
}
