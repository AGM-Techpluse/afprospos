<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unified Audit Trail — Phase 1 slice.
 *
 * Full event-driven audit capture (outbox integration, redaction pipeline)
 * is Phase 8 (AUD-BR-01..06). Phase 1 needs a working, append-only audit
 * sink NOW because its own exit criteria requires auditing role changes,
 * deactivations, and shop-context-sensitive administration
 * (Implementation Plan §3, Phase 1 goal). This table/writer combination
 * is forward-compatible with the Phase 8 expansion: Phase 8 adds richer
 * capture sources, not a schema change here.
 *
 * Append-only: no `updated_at` (DBDD §8 Immutability Model).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->string('module');
            $table->string('event_type');

            // Shared Kernel FK: valid database relationship.
            $table->foreignId('actor_staff_id')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->string('actor_role_snapshot')->nullable();

            // Application-level cross-module reference (subject may live in
            // any module) — deliberately NOT a foreign key (DBDD §4.2).
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');

            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->json('context')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(
                ['subject_type', 'subject_id', 'created_at'],
                'audit_logs_subject_history_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
