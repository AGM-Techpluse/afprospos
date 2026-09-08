<?php

declare(strict_types=1);

namespace Domain\RBAC\Domain\Entities;

use DateTimeImmutable;
use Domain\RBAC\Domain\Exceptions\ShopGrantAlreadyRevoked;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/**
 * BRD RBAC-05 / BLD RBAC-BR-09: a staff member's access to a specific
 * shop, kept as its own auditable record rather than a boolean flag —
 * revoking never deletes the row (DBDD §7 Delete Policy), so "who could
 * access shop X on date Y" stays answerable.
 */
final class StaffShopGrant
{
    private function __construct(
        private ?int $id,
        private readonly StaffId $staffId,
        private readonly ShopId $shopId,
        private readonly StaffId $grantedByStaffId,
        private readonly DateTimeImmutable $grantedAt,
        private ?DateTimeImmutable $revokedAt,
        private ?StaffId $revokedByStaffId,
    ) {}

    public static function grant(
        StaffId $staffId,
        ShopId $shopId,
        StaffId $grantedByStaffId,
        DateTimeImmutable $grantedAt,
    ): self {
        return new self(null, $staffId, $shopId, $grantedByStaffId, $grantedAt, null, null);
    }

    public static function reconstitute(
        int $id,
        StaffId $staffId,
        ShopId $shopId,
        StaffId $grantedByStaffId,
        DateTimeImmutable $grantedAt,
        ?DateTimeImmutable $revokedAt,
        ?StaffId $revokedByStaffId,
    ): self {
        return new self($id, $staffId, $shopId, $grantedByStaffId, $grantedAt, $revokedAt, $revokedByStaffId);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function staffId(): StaffId
    {
        return $this->staffId;
    }

    public function shopId(): ShopId
    {
        return $this->shopId;
    }

    public function grantedByStaffId(): StaffId
    {
        return $this->grantedByStaffId;
    }

    public function grantedAt(): DateTimeImmutable
    {
        return $this->grantedAt;
    }

    public function revokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function revokedByStaffId(): ?StaffId
    {
        return $this->revokedByStaffId;
    }

    public function isActive(): bool
    {
        return $this->revokedAt === null;
    }

    public function revoke(DateTimeImmutable $at, StaffId $revokedBy): void
    {
        if (! $this->isActive()) {
            throw ShopGrantAlreadyRevoked::forGrant($this->id ?? 0);
        }

        $this->revokedAt = $at;
        $this->revokedByStaffId = $revokedBy;
    }
}
