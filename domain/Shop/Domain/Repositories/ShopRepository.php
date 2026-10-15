<?php

declare(strict_types=1);

namespace Domain\Shop\Domain\Repositories;

use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shop\Domain\Entities\Shop;

interface ShopRepository
{
    public function get(ShopId $id): Shop;

    public function existsWithSkuPrefixCode(string $code): bool;

    public function existsWithSkuPrefixCodeExcept(string $code, ShopId $exceptId): bool;

    /**
     * @param  array<string, mixed>  $offlinePolicy
     */
    public function create(
        string $name,
        string $skuPrefixCode,
        string $address,
        string $contactPhone,
        string $contactEmail,
        array $offlinePolicy,
        bool $isWarehouse = false,
    ): ShopId;

    public function update(
        ShopId $id,
        string $name,
        string $skuPrefixCode,
        string $address,
        string $contactPhone,
        string $contactEmail,
        bool $isWarehouse = false,
    ): void;
}
