<?php

declare(strict_types=1);

namespace Domain\Shared\Domain\Exceptions;

/**
 * Raised when an actor attempts to act against a shop they are not
 * authorised for (BLD RBAC-BR-02/03; ADD §35 "never trust a
 * client-provided shop_id alone"). Maps to HTTP 403.
 */
final class ShopScopeViolation extends DomainException
{
    public static function forShop(int $shopId): self
    {
        return new self("Actor is not authorised to act on shop [{$shopId}].");
    }
}
