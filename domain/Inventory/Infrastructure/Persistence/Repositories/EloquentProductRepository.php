<?php

declare(strict_types=1);

namespace Domain\Inventory\Infrastructure\Persistence\Repositories;

use Domain\Inventory\Domain\Entities\Product;
use Domain\Inventory\Domain\Repositories\ProductRepository;
use Domain\Inventory\Domain\ValueObjects\ProductId;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\ProductRecord;

final class EloquentProductRepository implements ProductRepository
{
    public function get(ProductId $id): Product
    {
        $record = ProductRecord::query()->findOrFail($id->value);

        return Product::reconstitute(new ProductId($record->id), $record->brand, $record->model, $record->category);
    }

    public function findOrCreate(string $brand, string $model, string $category, string $unit = 'pcs', string $purpose = 'sale', ?int $supplierId = null): ProductId
    {
        $record = ProductRecord::query()->firstOrCreate(
            ['brand' => $brand, 'model' => $model, 'category' => $category],
            ['unit' => $unit, 'purpose' => $purpose, 'supplier_id' => $supplierId],
        );

        return new ProductId($record->id);
    }

    public function setImagePath(ProductId $id, string $imagePath): void
    {
        ProductRecord::query()->whereKey($id->value)->update(['image_path' => $imagePath]);
    }
}
