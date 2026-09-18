<?php

declare(strict_types=1);

namespace Domain\Warranty\Infrastructure\Persistence\Eloquent;

use Database\Factories\TradeInAssessmentRecordFactory;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $customer_id
 * @property int|null $related_checkout_id
 * @property array $device_description
 * @property int|null $assessed_value_minor
 * @property string $resolution_state
 * @property int|null $assessed_by_staff_id
 * @property int|null $approved_by_staff_id
 * @property Carbon $created_at
 */
final class TradeInAssessmentRecord extends Model
{
    use HasFactory;

    protected $table = 'trade_in_assessments';

    protected $fillable = [
        'customer_id',
        'related_checkout_id',
        'device_description',
        'assessed_value_minor',
        'resolution_state',
        'assessed_by_staff_id',
        'approved_by_staff_id',
    ];

    protected function casts(): array
    {
        return [
            'device_description' => 'array',
        ];
    }

    /** `customers.id` is Shared Kernel (CPNC §0.2) — this relation is allowed; there is no equivalent relation to Sales' own tables. */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerRecord::class, 'customer_id');
    }

    protected static function newFactory(): TradeInAssessmentRecordFactory
    {
        return TradeInAssessmentRecordFactory::new();
    }
}
