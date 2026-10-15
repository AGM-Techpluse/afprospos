<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repair workflow rework: completing the repair now leads to a quality check
 * (`qc_pending`) rather than straight to collection, and a job can be cancelled.
 * QC results are kept as history, since a failed check can loop back to diagnosis
 * and be checked again.
 */
return new class extends Migration
{
    private const STATUSES = [
        'received',
        'diagnosing',
        'awaiting_authorization',
        'payment_overdue',
        'expired_cancelled',
        'awaiting_parts',
        'in_progress',
        'qc_pending',
        'completed',
        'unrepairable',
        'failed_requires_resolution',
        'cancelled',
    ];

    public function up(): void
    {
        Schema::table('repair_jobs', function (Blueprint $table): void {
            $table->enum('repair_status', self::STATUSES)->default('received')->change();
        });

        Schema::create('repair_qc_checks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('repair_job_id');
            $table->enum('outcome', ['passed', 'failed', 'not_testable']);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('checked_by_staff_id');
            $table->timestamps();

            $table->index('repair_job_id');
        });

        Schema::table('payments_transactions', function (Blueprint $table): void {
            $table->enum('purpose', ['sale_payment', 'repair_down_payment', 'repair_final_payment', 'repair_diagnosis_fee'])
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments_transactions', function (Blueprint $table): void {
            $table->enum('purpose', ['sale_payment', 'repair_down_payment', 'repair_final_payment'])->change();
        });

        Schema::dropIfExists('repair_qc_checks');

        Schema::table('repair_jobs', function (Blueprint $table): void {
            $table->enum('repair_status', array_values(array_diff(self::STATUSES, ['qc_pending', 'cancelled'])))
                ->default('received')
                ->change();
        });
    }
};
