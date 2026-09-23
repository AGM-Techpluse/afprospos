<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * REP-BR-29: the technician's actual diagnosis — a stated root cause plus
 * a repairable/unrepairable/requires-further-assessment classification —
 * as a distinct fact from per-component test observations
 * (`repair_diagnoses`). Immutable once written: no `updated_at`, same
 * "no updated_at signals append-only" convention as `repair_diagnoses`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_diagnosis_conclusions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->foreignId('repair_job_id')
                ->constrained('repair_jobs')
                ->cascadeOnDelete()
                ->restrictOnUpdate();

            $table->text('root_cause');
            $table->enum('outcome', ['repairable', 'unrepairable', 'requires_further_assessment']);

            $table->foreignId('diagnosed_by_staff_id')
                ->constrained('staff')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->timestamp('created_at')->useCurrent();

            $table->index('repair_job_id', 'repair_diagnosis_conclusions_job_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_diagnosis_conclusions');
    }
};
