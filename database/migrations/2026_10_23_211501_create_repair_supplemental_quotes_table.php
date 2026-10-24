<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A "supplement" in repair-shop parlance — additional, separately-approved
 * cost discovered after the original quote was authorized (e.g. parts
 * turned out more expensive than estimated). Mirrors
 * `repair_payment_approvals`/`repair_down_payment_approvals` exactly:
 * mutable, not append-only — a rejected request may be re-requested as a
 * new row. Kept as its own additive ledger rather than an edit to
 * `repair_jobs.repair_cost_minor`, so the original quote and what was
 * added later (and why) both stay visible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_supplemental_quotes', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->foreignId('repair_job_id')
                ->constrained('repair_jobs')
                ->cascadeOnDelete()
                ->restrictOnUpdate();

            $table->bigInteger('amount_minor');
            $table->text('reason');

            $table->foreignId('requested_by_staff_id')
                ->constrained('staff')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            $table->foreignId('approved_by_staff_id')->nullable()
                ->constrained('staff')
                ->nullOnDelete()
                ->restrictOnUpdate();

            $table->timestamp('decided_at')->nullable();

            $table->timestamps();

            $table->index('repair_job_id', 'repair_supplemental_quotes_job_idx');
            $table->index('status', 'repair_supplemental_quotes_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_supplemental_quotes');
    }
};
