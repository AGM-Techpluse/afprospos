<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Entities;

use Domain\Inventory\Domain\ValueObjects\ProductId;

/**
 * The centralized, business-wide catalog concept (BLD §9.1) — "Apple
 * iPhone 15", distinct from a Sku ("iPhone 15/128GB/Black") and an
 * InventoryItem (one physical unit/IMEI).
 */
final class Product
{
    private function __construct(
        private readonly ProductId $id,
        private readonly string $brand,
        private readonly string $model,
        private readonly string $category,
    ) {}

    public static function reconstitute(ProductId $id, string $brand, string $model, string $category): self
    {
        return new self($id, $brand, $model, $category);
    }

    public function id(): ProductId
    {
        return $this->id;
    }

    public function brand(): string
    {
        return $this->brand;
    }

    public function model(): string
    {
        return $this->model;
    }

    public function category(): string
    {
        return $this->category;
    }
}
