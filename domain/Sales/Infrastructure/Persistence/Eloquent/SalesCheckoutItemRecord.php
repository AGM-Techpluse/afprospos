<?php

declare(strict_types=1);

namespace Domain\Sales\Infrastructure\Persistence\Eloquent;

use Database\Factories\SalesCheckoutItemRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $sales_checkout_id
 * @property int|null $inventory_item_id
 * @property int $sku_id
 * @property int $quantity
 * @property int $unit_price_minor
 * @property int|null $warranty_policy_id
 */
final class SalesCheckoutItemRecord extends Model
{
    use HasFactory;

    protected $table = 'sales_checkout_items';

    protected $fillable = [
        'sales_checkout_id', 'inventory_item_id', 'sku_id',
        'quantity', 'unit_price_minor', 'warranty_policy_id',
    ];

    public function checkout(): BelongsTo
    {
        return $this->belongsTo(SalesCheckoutRecord::class, 'sales_checkout_id');
    }

    protected static function newFactory()
    {
        return SalesCheckoutItemRecordFactory::new();
    }
}
