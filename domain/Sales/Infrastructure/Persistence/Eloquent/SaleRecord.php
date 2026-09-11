<?php

declare(strict_types=1);

namespace Domain\Sales\Infrastructure\Persistence\Eloquent;

use Database\Factories\SaleRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $sales_checkout_id
 * @property int $shop_id
 * @property int|null $customer_id
 * @property int $cashier_staff_id
 * @property int $total_minor
 * @property int|null $payment_transaction_id
 * @property string $payment_method
 * @property string|null $payment_reference
 * @property string $invoice_number
 */
final class SaleRecord extends Model
{
    use HasFactory;

    protected $table = 'sales_sales';

    /** DBDD §13.4: "deliberately no updated_at" — this is a finalized, immutable record. */
    const UPDATED_AT = null;

    protected $fillable = [
        'sales_checkout_id', 'shop_id', 'customer_id', 'cashier_staff_id',
        'total_minor', 'payment_transaction_id', 'payment_method',
        'payment_reference', 'invoice_number',
    ];

    public function checkout(): BelongsTo
    {
        return $this->belongsTo(SalesCheckoutRecord::class, 'sales_checkout_id');
    }

    protected static function newFactory()
    {
        return SaleRecordFactory::new();
    }
}
