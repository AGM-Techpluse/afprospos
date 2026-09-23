<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * REP-BR-27/28: a completed repair's `financial_status` may only change
 * via an approved payment-recording request — a separate Shop Owner
 * approval from the pre-work `repairs.approve` gate (down-payment
 * authorization). Mutable, not append-only: a rejected request may be
 * re-requested as a new row (mirrors `trade_in_assessments`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_payment_approvals', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->foreignId('repair_job_id')
                ->constrained('repair_jobs')
                ->cascadeOnDelete()
                ->restrictOnUpdate();

            $table->enum('requested_financial_status', ['partially_paid', 'fully_paid']);
            $table->bigInteger('amount_minor');

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

            $table->index('repair_job_id', 'repair_payment_approvals_job_idx');
            $table->index('status', 'repair_payment_approvals_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_payment_approvals');
    }
};
