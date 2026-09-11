<?php

declare(strict_types=1);

namespace Domain\Inventory\Infrastructure\Persistence\Eloquent;

use Database\Factories\ProductRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $brand
 * @property string $model
 * @property string $category
 */
final class ProductRecord extends Model
{
    use HasFactory;

    protected $table = 'inventory_products';

    protected $fillable = ['brand', 'model', 'category'];

    public function skus(): HasMany
    {
        return $this->hasMany(SkuRecord::class, 'product_id');
    }

    protected static function newFactory()
    {
        return ProductRecordFactory::new();
    }
}
