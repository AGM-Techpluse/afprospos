<?php

declare(strict_types=1);

namespace Domain\Notifications\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Append-only — same discipline as AuditLogRecord (no update()/delete() call anywhere), just not covered by that dedicated arch test since it's a different table. */
final class NotificationDeliveryAttemptRecord extends Model
{
    public $timestamps = false;

    protected $table = 'notification_delivery_attempts';

    protected $fillable = [
        'notification_event_id',
        'channel',
        'provider',
        'attempt_number',
        'status',
        'provider_response',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (self $record): void {
            $record->created_at ??= now();
        });
    }

    public function notificationEvent(): BelongsTo
    {
        return $this->belongsTo(NotificationEventRecord::class, 'notification_event_id');
    }
}
