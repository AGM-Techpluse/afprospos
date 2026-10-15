<?php

declare(strict_types=1);

namespace Domain\Inventory\Infrastructure\Persistence\Eloquent;

use Database\Factories\SkuRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $product_id
 * @property string $sku_code
 * @property array<string, mixed> $attributes
 * @property bool $is_serialized
 * @property int $cost_price_minor
 * @property float $markup_percent
 * @property int $selling_price_minor
 * @property bool $selling_price_overridden
 * @property int|null $low_stock_threshold
 * @property bool $is_commissionable
 * @property int|null $commission_amount_minor
 */
final class SkuRecord extends Model
{
    use HasFactory;

    protected $table = 'inventory_skus';

    protected $fillable = [
        'product_id',
        'sku_code',
        'attributes',
        'attributes_signature',
        'is_serialized',
        'cost_price_minor',
        'markup_percent',
        'selling_price_minor',
        'selling_price_overridden',
        'low_stock_threshold',
        'is_commissionable',
        'commission_amount_minor',
    ];

    protected $casts = [
        'attributes' => 'array',
        'is_serialized' => 'boolean',
        'selling_price_overridden' => 'boolean',
        'markup_percent' => 'float',
        'is_commissionable' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductRecord::class, 'product_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryItemRecord::class, 'sku_id');
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(InventoryStockLevelRecord::class, 'sku_id');
    }

    protected static function newFactory()
    {
        return SkuRecordFactory::new();
    }
}
