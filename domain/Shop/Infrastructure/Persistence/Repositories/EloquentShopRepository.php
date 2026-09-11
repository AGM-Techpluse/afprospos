<?php

declare(strict_types=1);

namespace Domain\Shop\Infrastructure\Persistence\Repositories;

use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shop\Domain\Entities\Shop;
use Domain\Shop\Domain\Repositories\ShopRepository;

final class EloquentShopRepository implements ShopRepository
{
    public function get(ShopId $id): Shop
    {
        $record = ShopRecord::query()->findOrFail($id->value);

        return Shop::reconstitute(
            id: new ShopId($record->id),
            name: $record->name,
            skuPrefixCode: $record->sku_prefix_code,
            status: $record->status,
        );
    }

    public function existsWithSkuPrefixCode(string $code): bool
    {
        return ShopRecord::query()->where('sku_prefix_code', $code)->exists();
    }

    public function existsWithSkuPrefixCodeExcept(string $code, ShopId $exceptId): bool
    {
        return ShopRecord::query()
            ->where('sku_prefix_code', $code)
            ->where('id', '!=', $exceptId->value)
            ->exists();
    }

    public function create(
        string $name,
        string $skuPrefixCode,
        string $address,
        string $contactPhone,
        string $contactEmail,
        array $offlinePolicy,
    ): ShopId {
        $record = ShopRecord::query()->create([
            'name' => $name,
            'sku_prefix_code' => $skuPrefixCode,
            'address' => $address,
            'contact_phone' => $contactPhone,
            'contact_email' => $contactEmail,
            'offline_policy' => $offlinePolicy,
            'status' => 'active',
        ]);

        return new ShopId($record->id);
    }

    public function update(
        ShopId $id,
        string $name,
        string $skuPrefixCode,
        string $address,
        string $contactPhone,
        string $contactEmail,
    ): void {
        ShopRecord::query()->findOrFail($id->value)->update([
            'name' => $name,
            'sku_prefix_code' => $skuPrefixCode,
            'address' => $address,
            'contact_phone' => $contactPhone,
            'contact_email' => $contactEmail,
        ]);
    }
}
