<?php

declare(strict_types=1);

namespace Domain\Sales\Infrastructure\Persistence\Eloquent;

use Database\Factories\SalesCheckoutRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $shop_id
 * @property int|null $customer_id
 * @property int $cashier_staff_id
 * @property string $status
 * @property Carbon $reservation_expires_at
 * @property int $subtotal_minor
 * @property int $discount_minor
 * @property int $total_minor
 * @property int|null $payment_transaction_id
 */
final class SalesCheckoutRecord extends Model
{
    use HasFactory;

    protected $table = 'sales_checkouts';

    protected $fillable = [
        'shop_id', 'customer_id', 'cashier_staff_id', 'status',
        'reservation_expires_at', 'subtotal_minor', 'discount_minor',
        'total_minor', 'payment_transaction_id',
    ];

    protected $casts = [
        'reservation_expires_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SalesCheckoutItemRecord::class, 'sales_checkout_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(SalesCheckoutAdjustmentRecord::class, 'sales_checkout_id');
    }

    protected static function newFactory()
    {
        return SalesCheckoutRecordFactory::new();
    }
}
