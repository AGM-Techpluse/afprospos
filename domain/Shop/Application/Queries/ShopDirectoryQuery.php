<?php

declare(strict_types=1);

namespace Domain\Shop\Application\Queries;

use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;

/**
 * Read-side query backing the Admin shops list/show/edit screens. Unlike
 * AccessibleShopsQuery (grant-filtered, built for the shop-switcher),
 * this is the unfiltered administrative view — an actor with shops.view
 * sees every shop regardless of their own shop grants.
 */
final class ShopDirectoryQuery
{
    /** @return array<int, array{id:int, name:string, sku_prefix_code:string, address:string, contact_phone:string, contact_email:string, status:string}> */
    public function all(): array
    {
        return ShopRecord::query()
            ->orderBy('name')
            ->get()
            ->map(static fn (ShopRecord $shop): array => self::toArray($shop))
            ->all();
    }

    /**
     * @return array{data: array<int, array{id:int, name:string, sku_prefix_code:string, address:string, contact_phone:string, contact_email:string, status:string}>, current_page:int, last_page:int, per_page:int, total:int}
     */
    public function paginate(?string $search, ?string $status, int $page, int $perPage = 20): array
    {
        $query = ShopRecord::query()->orderBy('name');

        if ($search !== null && $search !== '') {
            $query->where(function ($inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('sku_prefix_code', 'like', "%{$search}%");
            });
        }

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->getCollection()->map(static fn (ShopRecord $shop): array => self::toArray($shop))->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    /** @return array{id:int, name:string, sku_prefix_code:string, address:string, contact_phone:string, contact_email:string, status:string}|null */
    public function find(int $id): ?array
    {
        $shop = ShopRecord::query()->find($id);

        return $shop !== null ? self::toArray($shop) : null;
    }

    /** @return array{id:int, name:string, sku_prefix_code:string, address:string, contact_phone:string, contact_email:string, status:string} */
    private static function toArray(ShopRecord $shop): array
    {
        return [
            'id' => $shop->id,
            'name' => $shop->name,
            'sku_prefix_code' => $shop->sku_prefix_code,
            'address' => $shop->address,
            'contact_phone' => $shop->contact_phone,
            'contact_email' => $shop->contact_email,
            'status' => $shop->status,
        ];
    }
}
