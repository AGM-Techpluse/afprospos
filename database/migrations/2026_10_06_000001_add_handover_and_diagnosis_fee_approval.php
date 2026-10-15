<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repair flow redesign:
 * - `handover`: quality check passed, the device waits for handover (frontdesk confirms,
 *   manager approves full payment). Releasing it from collection sets `completed`.
 * - `repair_diagnosis_fees`: the diagnosis fee is set by frontdesk, approved (or declined)
 *   by the manager, then collected as its own payment. Only an approved fee can be collected.
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
        'handover',
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

        Schema::create('repair_diagnosis_fees', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('repair_job_id')->unique();
            $table->unsignedBigInteger('amount_minor');
            $table->enum('decision', ['pending', 'approved', 'declined'])->default('pending');
            $table->unsignedBigInteger('set_by_staff_id');
            $table->unsignedBigInteger('decided_by_staff_id')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_diagnosis_fees');

        Schema::table('repair_jobs', function (Blueprint $table): void {
            $table->enum('repair_status', array_values(array_diff(self::STATUSES, ['handover'])))->default('received')->change();
        });
    }
};
