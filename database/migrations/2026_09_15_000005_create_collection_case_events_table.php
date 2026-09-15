<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Append-only event log (DBDD §12.2) — no `updated_at`, same convention as `audit_logs`/`repair_diagnoses`. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_case_events', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->foreignId('collection_case_id')
                ->constrained('collection_cases')
                ->cascadeOnDelete()
                ->restrictOnUpdate();

            $table->enum('event_type', [
                'deadline_set',
                'notified',
                'extended',
                'fee_accrued',
                'marked_overdue',
                'marked_abandoned',
                'administrative_resolution',
            ]);

            $table->foreignId('actor_staff_id')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete()
                ->restrictOnUpdate();

            $table->json('detail');

            $table->timestamp('created_at')->useCurrent();

            $table->index('collection_case_id', 'collection_case_events_case_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_case_events');
    }
};
