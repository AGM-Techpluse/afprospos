<?php

declare(strict_types=1);

namespace Domain\Sales\Infrastructure\Persistence\Eloquent;

use Database\Factories\SalesCheckoutAdjustmentRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $sales_checkout_id
 * @property string $type
 * @property int $source_id
 * @property int $amount_minor
 * @property int $applied_order
 */
final class SalesCheckoutAdjustmentRecord extends Model
{
    use HasFactory;

    protected $table = 'sales_checkout_adjustments';

    protected $fillable = ['sales_checkout_id', 'type', 'source_id', 'amount_minor', 'applied_order'];

    public function checkout(): BelongsTo
    {
        return $this->belongsTo(SalesCheckoutRecord::class, 'sales_checkout_id');
    }

    protected static function newFactory()
    {
        return SalesCheckoutAdjustmentRecordFactory::new();
    }
}
