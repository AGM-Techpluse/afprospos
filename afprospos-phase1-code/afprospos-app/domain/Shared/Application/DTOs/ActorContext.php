<?php

declare(strict_types=1);

namespace Domain\Shared\Application\DTOs;

use Domain\Shared\Domain\ValueObjects\StaffId;

/**
 * The authenticated staff actor's resolved authorization context for the
 * current request: their effective permissions (union across every
 * assigned role — BLD RBAC-BR-02), the shops they may act on, and which
 * shop is currently active.
 *
 * Built once per request by ResolveShopContext middleware and bound into
 * the container, so every Controller/Handler downstream sees the same
 * authorised view of "who is this and what can they touch" (ADD §34/§35).
 *
 * Customer-side requests do not use ActorContext — customers have no
 * roles/shop scope (BRD RBAC-07); customer identity flows through the
 * `customer` auth guard directly.
 */
final readonly class ActorContext
{
    /**
     * @param  string[]  $effectivePermissions  union of permissions granted
     *                                          by every role assigned to this staff member
     * @param  int[]  $shopIds  shop IDs this staff member may act on;
     *                          for an owner this is every active shop, resolved dynamically
     *                          rather than stored, so a newly created shop is immediately
     *                          in scope without a grant row (BLD "Shop Owner may operate
     *                          across all shops")
     */
    public function __construct(
        public StaffId $staffId,
        public array $effectivePermissions,
        public array $shopIds,
        public ?int $activeShopId,
        public bool $isOwner,
    ) {}

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->effectivePermissions, true);
    }

    public function canAccessShop(int $shopId): bool
    {
        return $this->isOwner || in_array($shopId, $this->shopIds, true);
    }
}
