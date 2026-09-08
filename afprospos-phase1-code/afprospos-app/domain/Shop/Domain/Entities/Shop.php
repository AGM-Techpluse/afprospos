<?php

declare(strict_types=1);

namespace Domain\Shop\Domain\Entities;

use Domain\Shared\Domain\ValueObjects\ShopId;

final class Shop
{
    private function __construct(
        private readonly ShopId $id,
        private readonly string $name,
        private readonly string $skuPrefixCode,
        private readonly string $status,
    ) {}

    public static function reconstitute(ShopId $id, string $name, string $skuPrefixCode, string $status): self
    {
        return new self($id, $name, $skuPrefixCode, $status);
    }

    public function id(): ShopId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function skuPrefixCode(): string
    {
        return $this->skuPrefixCode;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
