<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Repositories;

use Domain\Inventory\Domain\Entities\Product;
use Domain\Inventory\Domain\ValueObjects\ProductId;

interface ProductRepository
{
    public function get(ProductId $id): Product;

    /** BLD §9.1: the SKU belongs to the catalog, so re-adding the same brand/model/category reuses the existing Product. */
    /** Unit, purpose and supplier are set when the product is first created; an existing product keeps its own. */
    public function findOrCreate(string $brand, string $model, string $category, string $unit = 'pcs', string $purpose = 'sale', ?int $supplierId = null): ProductId;

    public function setImagePath(ProductId $id, string $imagePath): void;

    public function setDescription(ProductId $id, ?string $description): void;
}
