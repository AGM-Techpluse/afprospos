<?php

declare(strict_types=1);

namespace Domain\Inventory\Infrastructure\Persistence\Eloquent;

use Database\Factories\InventoryStockLevelRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $sku_id
 * @property int $shop_id
 * @property int $on_hand
 * @property int $reserved
 * @property int $version
 */
final class InventoryStockLevelRecord extends Model
{
    use HasFactory;

    protected $table = 'inventory_stock_levels';

    protected $fillable = ['sku_id', 'shop_id', 'on_hand', 'reserved', 'version'];

    public function sku(): BelongsTo
    {
        return $this->belongsTo(SkuRecord::class, 'sku_id');
    }

    protected static function newFactory()
    {
        return InventoryStockLevelRecordFactory::new();
    }
}
