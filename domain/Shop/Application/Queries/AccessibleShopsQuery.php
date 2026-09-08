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
 */
final class AccessibleShopsQuery
{
    public function __construct(private readonly StaffShopGrantsQuery $grants) {}

    /** @return array<int, array{id:int, name:string, sku_prefix_code:string}> */
    public function forStaff(int $staffId, bool $isOwner): array
    {
        $query = ShopRecord::query()->where('status', 'active');

        if (! $isOwner) {
            $query->whereIn('id', $this->grants->activeShopIdsForStaff($staffId));
        }

        return $query->get(['id', 'name', 'sku_prefix_code'])
            ->map(static fn (ShopRecord $shop): array => [
                'id' => $shop->id,
                'name' => $shop->name,
                'sku_prefix_code' => $shop->sku_prefix_code,
            ])
            ->all();
    }
}
