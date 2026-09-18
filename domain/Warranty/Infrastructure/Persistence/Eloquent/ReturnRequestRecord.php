<?php

declare(strict_types=1);

namespace Domain\Warranty\Infrastructure\Persistence\Eloquent;

use Database\Factories\ReturnRequestRecordFactory;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $sale_id
 * @property int $customer_id
 * @property string $resolution_state
 * @property Carbon $return_window_expires_at
 * @property string|null $denial_reason
 * @property int|null $override_approved_by_staff_id
 * @property int|null $refund_transaction_id
 * @property Carbon $created_at
 */
final class ReturnRequestRecord extends Model
{
    use HasFactory;

    protected $table = 'return_requests';

    protected $fillable = [
        'sale_id',
        'customer_id',
        'resolution_state',
        'return_window_expires_at',
        'denial_reason',
        'override_approved_by_staff_id',
        'refund_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'return_window_expires_at' => 'datetime',
        ];
    }

    /** `customers.id` is Shared Kernel (CPNC §0.2) — this relation is allowed; there is no equivalent relation to Sales' own tables. */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerRecord::class, 'customer_id');
    }

    protected static function newFactory(): ReturnRequestRecordFactory
    {
        return ReturnRequestRecordFactory::new();
    }
}
