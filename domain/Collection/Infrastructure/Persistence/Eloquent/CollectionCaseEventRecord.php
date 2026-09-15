<?php

declare(strict_types=1);

namespace Domain\Collection\Infrastructure\Persistence\Eloquent;

use Database\Factories\CollectionCaseEventRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $collection_case_id
 * @property string $event_type
 * @property int|null $actor_staff_id
 * @property array $detail
 * @property Carbon $created_at
 */
final class CollectionCaseEventRecord extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $table = 'collection_case_events';

    protected $fillable = [
        'collection_case_id',
        'event_type',
        'actor_staff_id',
        'detail',
    ];

    protected function casts(): array
    {
        return [
            'detail' => 'array',
        ];
    }

    protected static function newFactory()
    {
        return CollectionCaseEventRecordFactory::new();
    }
}
