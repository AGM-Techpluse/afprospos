<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Repositories;

use Domain\Inventory\Domain\Entities\Product;
use Domain\Inventory\Domain\ValueObjects\ProductId;

interface ProductRepository
{
    public function get(ProductId $id): Product;

    /** BLD §9.1: the SKU belongs to the catalog, so re-adding the same brand/model/category reuses the existing Product. */
    public function findOrCreate(string $brand, string $model, string $category): ProductId;
}
