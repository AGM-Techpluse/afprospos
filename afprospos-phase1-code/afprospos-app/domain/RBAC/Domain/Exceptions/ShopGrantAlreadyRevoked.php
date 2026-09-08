<?php

declare(strict_types=1);

namespace Domain\RBAC\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class ShopGrantAlreadyRevoked extends DomainException
{
    public static function forGrant(int $grantId): self
    {
        return new self("Shop access grant [{$grantId}] is already revoked.");
    }
}
