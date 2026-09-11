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

    public function findOrCreate(string $brand, string $model, string $category): ProductId
    {
        $record = ProductRecord::query()->firstOrCreate(
            ['brand' => $brand, 'model' => $model, 'category' => $category],
        );

        return new ProductId($record->id);
    }
}
