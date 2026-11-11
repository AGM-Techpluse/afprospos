<?php

declare(strict_types=1);

namespace Domain\Shop\Application\Queries;

use Domain\RBAC\Application\Queries\StaffShopGrantsQuery;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;

/**
 * Read-side query object. Feeds the shop switcher in the Admin shell —
 * an owner sees every active shop, everyone else sees only shops they
 * hold an active grant for (BRD SHOP-06 / BLD "Shop Owner may operate
 * across all shops").
 *
 * Bound as a singleton (ShopServiceProvider) specifically so the
 * per-($staffId, $isOwner) memoization below actually holds across a
 * single request: HandleInertiaRequests, ResolveShopContext, and up to
 * a dozen+ Admin controllers each independently call forStaff() for the
 * same actor on the same request, and without a shared instance every
 * one of those would re-run the query from scratch.
 */
final class AccessibleShopsQuery
{
    /** @var array<string, array<int, array{id:int, name:string, sku_prefix_code:string, is_warehouse:bool}>> */
    private array $cache = [];

    public function __construct(private readonly StaffShopGrantsQuery $grants) {}

    /** @return array<int, array{id:int, name:string, sku_prefix_code:string, is_warehouse:bool}> */
    public function forStaff(int $staffId, bool $isOwner): array
    {
        $cacheKey = "{$staffId}:".($isOwner ? '1' : '0');

        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $query = ShopRecord::query()->where('status', 'active');

        if (! $isOwner) {
            $query->whereIn('id', $this->grants->activeShopIdsForStaff($staffId));
        }

        return $this->cache[$cacheKey] = $query->get(['id', 'name', 'sku_prefix_code', 'is_warehouse'])
            ->map(static fn (ShopRecord $shop): array => [
                'id' => $shop->id,
                'name' => $shop->name,
                'sku_prefix_code' => $shop->sku_prefix_code,
                'is_warehouse' => (bool) $shop->is_warehouse,
            ])
            ->all();
    }
}
