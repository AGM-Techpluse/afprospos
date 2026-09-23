<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors `repair_payment_approvals` exactly, but for the pre-work down
 * payment: frontdesk records that the customer paid, Manager/Shop Owner
 * approves before `RepairJob::grantAuthorization()` and the real payment
 * transaction are triggered. Kept as its own table rather than folded
 * into `repair_payment_approvals` since the two gate structurally
 * different moments in the repair lifecycle (before vs. after work).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_down_payment_approvals', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->foreignId('repair_job_id')
                ->constrained('repair_jobs')
                ->cascadeOnDelete()
                ->restrictOnUpdate();

            $table->bigInteger('amount_minor');
            $table->enum('method', ['cash', 'pos_terminal', 'bank_transfer']);

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

            $table->index('repair_job_id', 'repair_down_payment_approvals_job_idx');
            $table->index('status', 'repair_down_payment_approvals_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_down_payment_approvals');
    }
};
