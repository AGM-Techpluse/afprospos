<?php

declare(strict_types=1);

namespace Domain\Shop\Domain\Entities;

use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shop\Domain\Exceptions\ShopAlreadyActive;
use Domain\Shop\Domain\Exceptions\ShopAlreadyDeactivated;

final class Shop
{
    private function __construct(
        private readonly ShopId $id,
        private readonly string $name,
        private readonly string $skuPrefixCode,
        private string $status,
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

    public function status(): string
    {
        return $this->status;
    }

    /** The caller (Handler) is responsible for auditing this — this method only enforces the state-transition invariant itself. */
    public function deactivate(): void
    {
        if (! $this->isActive()) {
            throw ShopAlreadyDeactivated::forShop($this->id);
        }

        $this->status = 'inactive';
    }

    public function activate(): void
    {
        if ($this->isActive()) {
            throw ShopAlreadyActive::forShop($this->id);
        }

        $this->status = 'active';
    }
}
