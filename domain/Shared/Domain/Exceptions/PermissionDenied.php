<?php

declare(strict_types=1);

namespace Domain\Shared\Domain\Exceptions;

/**
 * Raised when an actor's effective permission set (union across all
 * assigned roles — BLD RBAC-BR-02) does not include a required
 * permission. Maps to HTTP 403.
 */
final class PermissionDenied extends DomainException
{
    public static function forPermission(string $permission): self
    {
        return new self("Actor does not hold the required permission [{$permission}].");
    }
}
