<?php

declare(strict_types=1);

namespace Domain\Collection\Infrastructure\Persistence\Eloquent;

use Database\Factories\CollectionCaseRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $source_type
 * @property int $source_id
 * @property int $shop_id
 * @property int $originating_shop_id
 * @property string $context
 * @property string $status
 * @property Carbon $collection_deadline_at
 * @property Carbon|null $abandonment_threshold_at
 * @property array|null $storage_fee_policy_snapshot
 * @property int $accrued_storage_fee_minor
 * @property string|null $shop_override_reason
 */
final class CollectionCaseRecord extends Model
{
    use HasFactory;

    protected $table = 'collection_cases';

    protected $fillable = [
        'source_type',
        'source_id',
        'shop_id',
        'originating_shop_id',
        'context',
        'status',
        'collection_deadline_at',
        'abandonment_threshold_at',
        'storage_fee_policy_snapshot',
        'accrued_storage_fee_minor',
        'shop_override_reason',
    ];

    protected function casts(): array
    {
        return [
            'collection_deadline_at' => 'datetime',
            'abandonment_threshold_at' => 'datetime',
            'storage_fee_policy_snapshot' => 'array',
        ];
    }

    protected static function newFactory()
    {
        return CollectionCaseRecordFactory::new();
    }
}
