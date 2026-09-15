<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DBDD §12.1. `source_id` is deliberately an ID-only cross-module
 * reference (repair_job today; warranty_claim arrives with Phase 7) —
 * no foreign key. `shop_id` is the physical holding/collection shop and
 * is not required to equal `originating_shop_id`.
 * `storage_fee_policy_snapshot`/`accrued_storage_fee_minor` back a
 * simple flat-daily-rate accrual model (Phase 6 scope decision — the
 * DBDD specifies these columns but no accrual formula).
 * `(status, collection_deadline_at)` is an additive index beyond DBDD's
 * literal list, needed for the daily deadline-processing scan — same
 * precedent as `payments_transactions(status, created_at)`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_cases', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->enum('source_type', ['repair_job', 'warranty_claim']);
            $table->unsignedBigInteger('source_id');

            $table->foreignId('shop_id')
                ->constrained('shops')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->foreignId('originating_shop_id')
                ->constrained('shops')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->enum('context', ['ready_for_collection', 'ready_for_return']);
            $table->enum('status', ['pending', 'overdue', 'abandoned', 'resolved'])->default('pending');

            $table->dateTime('collection_deadline_at');
            $table->dateTime('abandonment_threshold_at')->nullable();

            $table->json('storage_fee_policy_snapshot')->nullable();
            $table->bigInteger('accrued_storage_fee_minor')->default(0);

            $table->text('shop_override_reason')->nullable();

            $table->timestamps();

            $table->index(['source_type', 'source_id'], 'collection_cases_source_idx');
            $table->index(['status', 'collection_deadline_at'], 'collection_cases_status_deadline_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_cases');
    }
};
