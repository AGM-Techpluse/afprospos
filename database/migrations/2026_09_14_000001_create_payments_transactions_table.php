<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payments is a standalone bounded context (DBDD §16.1) — `payable_id` is
 * a cross-module ID only, deliberately with no foreign key, since
 * `payable_type` varies and the DBDD explicitly forbids a
 * `payments_transactions.payable_id -> sales_sales.id` constraint (§4.2).
 * `(status, created_at)` is an additive index beyond DBDD's literal list,
 * needed for the Admin reconciliation/dispute queue — same precedent as
 * `sales_checkouts_status_expiry_idx`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments_transactions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->enum('payable_type', ['sales_checkout', 'repair_job', 'warranty_claim', 'return_request']);
            $table->unsignedBigInteger('payable_id');

            $table->enum('method', ['cash', 'pos_terminal', 'bank_transfer', 'in_app']);
            $table->bigInteger('amount_minor');

            $table->enum('status', ['pending', 'payment_pending_confirmation', 'confirmed', 'disputed', 'refunded', 'exception'])
                ->default('pending');

            $table->string('provider_reference')->nullable();

            $table->foreignId('confirmed_by_staff_id')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete()
                ->restrictOnUpdate();

            $table->dateTime('dispute_opened_at')->nullable();
            $table->string('dispute_proof_reference')->nullable();

            $table->foreignId('dispute_resolved_by_staff_id')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete()
                ->restrictOnUpdate();

            $table->timestamps();

            $table->index(['payable_type', 'payable_id'], 'payments_transactions_payable_idx');
            $table->index(['status', 'created_at'], 'payments_transactions_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments_transactions');
    }
};
