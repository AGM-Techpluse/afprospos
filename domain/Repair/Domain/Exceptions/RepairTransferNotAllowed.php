<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class RepairTransferNotAllowed extends DomainException
{
    public static function sameShop(int $shopId): self
    {
        return new self("Repair is already assigned to shop [{$shopId}].");
    }

    public static function closed(int $repairJobId, string $status): self
    {
        return new self("Repair [{$repairJobId}] cannot be transferred while it is [{$status}].");
    }

    public static function collectionOpen(int $repairJobId): self
    {
        return new self("Repair [{$repairJobId}] already has a collection case. Transfer the collection case instead.");
    }

    public static function reservedParts(int $repairJobId): self
    {
        return new self("Repair [{$repairJobId}] has reserved parts. Release them or transfer the stock separately before moving the repair.");
    }
}
