<?php

declare(strict_types=1);

namespace Domain\Inventory\Infrastructure\Persistence\Eloquent;

use Database\Factories\InventoryTransferRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $sku_id
 * @property int|null $inventory_item_id
 * @property int|null $quantity
 * @property int $from_shop_id
 * @property int $to_shop_id
 * @property int $initiated_by_staff_id
 * @property int|null $received_by_staff_id
 * @property string $status
 */
final class InventoryTransferRecord extends Model
{
    use HasFactory;

    protected $table = 'inventory_transfers';

    protected $fillable = [
        'sku_id',
        'inventory_item_id',
        'quantity',
        'from_shop_id',
        'to_shop_id',
        'initiated_by_staff_id',
        'received_by_staff_id',
        'status',
    ];

    public function sku(): BelongsTo
    {
        return $this->belongsTo(SkuRecord::class, 'sku_id');
    }

    protected static function newFactory()
    {
        return InventoryTransferRecordFactory::new();
    }
}
