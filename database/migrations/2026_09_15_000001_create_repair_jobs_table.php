<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repair job aggregate root (DBDD §11.1). `down_payment_deadline_at`
 * doubles as the authorization-response deadline for a zero-down-payment
 * job too — a repair still needs a yes/no within that window (Phase 6
 * scope decision: one deadline concept, not two).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_jobs', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->foreignId('shop_id')
                ->constrained('shops')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->string('device_make', 100);
            $table->string('device_model', 150);

            $table->foreignId('technician_staff_id')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete()
                ->restrictOnUpdate();

            $table->bigInteger('labour_charge_minor');
            $table->bigInteger('down_payment_required_minor')->nullable();
            $table->dateTime('down_payment_deadline_at')->nullable();

            $table->enum('repair_status', [
                'received',
                'diagnosing',
                'awaiting_authorization',
                'payment_overdue',
                'expired_cancelled',
                'awaiting_parts',
                'in_progress',
                'completed',
                'unrepairable',
                'failed_requires_resolution',
            ])->default('received');

            $table->enum('financial_status', ['unpaid', 'partially_paid', 'fully_paid'])->default('unpaid');

            $table->date('estimated_collection_date')->nullable();

            $table->enum('unrepairable_settlement_state', ['n/a', 'pending_decision', 'refunded', 'retained'])->nullable();

            $table->timestamps();

            $table->index(['shop_id', 'repair_status'], 'repair_jobs_shop_status_idx');
            $table->index('customer_id', 'repair_jobs_customer_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_jobs');
    }
};
