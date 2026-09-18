<?php

declare(strict_types=1);

namespace Domain\Notifications\Infrastructure\Persistence\Eloquent;

use Database\Factories\NotificationEventRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $event_type
 * @property string $source_module
 * @property int $source_id
 * @property string $recipient_type
 * @property int $recipient_id
 * @property string $category
 * @property array<string, mixed>|null $payload
 * @property string $status
 * @property Carbon|null $read_at
 * @property Carbon $created_at
 */
final class NotificationEventRecord extends Model
{
    use HasFactory;

    protected $table = 'notification_events';

    protected $fillable = [
        'event_type',
        'source_module',
        'source_id',
        'recipient_type',
        'recipient_id',
        'category',
        'payload',
        'status',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'read_at' => 'datetime',
        ];
    }

    protected static function newFactory(): NotificationEventRecordFactory
    {
        return NotificationEventRecordFactory::new();
    }
}
