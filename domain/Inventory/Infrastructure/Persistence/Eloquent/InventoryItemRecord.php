<?php

declare(strict_types=1);

namespace Domain\Inventory\Infrastructure\Persistence\Eloquent;

use Database\Factories\InventoryItemRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $sku_id
 * @property string $imei
 * @property int $current_shop_id
 * @property string $condition
 * @property string $status
 * @property string|null $reserved_by_type
 * @property int|null $reserved_by_id
 * @property int $version
 */
final class InventoryItemRecord extends Model
{
    use HasFactory;

    protected $table = 'inventory_items';

    protected $fillable = [
        'sku_id',
        'imei',
        'current_shop_id',
        'condition',
        'status',
        'reserved_by_type',
        'reserved_by_id',
        'version',
    ];

    public function sku(): BelongsTo
    {
        return $this->belongsTo(SkuRecord::class, 'sku_id');
    }

    protected static function newFactory()
    {
        return InventoryItemRecordFactory::new();
    }
}
