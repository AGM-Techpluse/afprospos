<?php

declare(strict_types=1);

namespace Domain\Payments\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $payment_transaction_id
 * @property int $amount_minor
 * @property int|null $refunded_by_staff_id
 * @property string $source
 */
final class PaymentRefundRecord extends Model
{
    protected $table = 'payment_refunds';

    /** Append-only — deliberately no updated_at. */
    const UPDATED_AT = null;

    protected $fillable = [
        'payment_transaction_id', 'amount_minor', 'refunded_by_staff_id', 'source',
    ];
}
