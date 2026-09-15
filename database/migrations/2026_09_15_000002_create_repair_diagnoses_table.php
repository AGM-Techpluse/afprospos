<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-component diagnostic observations (DBDD §11.2/§17.2) — never a
 * single `is_repairable` boolean. Immutable once written: no
 * `updated_at`, same "no updated_at signals append-only" convention as
 * `audit_logs`. `outcome` is null on every row except the one that
 * finalizes a diagnosis session (Phase 6 scope decision: no separate
 * "diagnosis session" table or synthetic summary row — the finalizing
 * `RecordDiagnosisCommand` call carries both a real component
 * observation and the outcome in one row).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_diagnoses', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->foreignId('repair_job_id')
                ->constrained('repair_jobs')
                ->cascadeOnDelete()
                ->restrictOnUpdate();

            $table->string('component', 100);
            $table->enum('condition', ['working', 'faulty', 'not_tested', 'unable_to_test']);
            $table->text('notes')->nullable();
            $table->enum('outcome', ['repairable', 'unrepairable', 'requires_further_assessment'])->nullable();

            $table->foreignId('diagnosed_by_staff_id')
                ->constrained('staff')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->timestamp('created_at')->useCurrent();

            $table->index('repair_job_id', 'repair_diagnoses_job_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_diagnoses');
    }
};
