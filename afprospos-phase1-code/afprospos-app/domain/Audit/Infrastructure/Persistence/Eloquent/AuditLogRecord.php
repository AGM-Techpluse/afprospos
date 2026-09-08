<?php

declare(strict_types=1);

namespace Domain\Audit\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * Append-only. Deliberately exposes no update()/delete() call anywhere in
 * this codebase (CPNC §4.5 / ADD §40) — corrections are new audit rows,
 * never edits to an existing one (DBDD §8 Immutability Model).
 *
 * $timestamps is disabled because this table only has `created_at`
 * (no `updated_at` — that is the structural signal that it is an
 * event/ledger record, not a mutable entity, per DBDD §8).
 */
final class AuditLogRecord extends Model
{
    public $timestamps = false;

    protected $table = 'audit_logs';

    protected $fillable = [
        'module',
        'event_type',
        'actor_staff_id',
        'actor_role_snapshot',
        'subject_type',
        'subject_id',
        'before_state',
        'after_state',
        'context',
        'created_at',
    ];

    protected $casts = [
        'before_state' => 'array',
        'after_state' => 'array',
        'context' => 'array',
        'created_at' => 'datetime',
    ];
}
