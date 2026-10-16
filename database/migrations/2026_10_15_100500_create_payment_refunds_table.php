<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only refund ledger (mirrors commission_ledger_entries) — a
 * transaction's refunds are never represented as a mutated amount on the
 * original row, always as new rows here, so multiple partial refunds
 * against the same transaction over time are each preserved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_refunds', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->foreignId('payment_transaction_id')
                ->constrained('payments_transactions')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->unsignedBigInteger('amount_minor');

            $table->foreignId('refunded_by_staff_id')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete()
                ->restrictOnUpdate();

            $table->enum('source', ['manual_refund', 'dispute_resolution']);

            // Do NOT call $table->timestamps() — this table is append-only and deliberately has no updated_at (mirrors commission_ledger_entries).
            $table->timestamp('created_at')->useCurrent();

            $table->index('payment_transaction_id', 'payment_refunds_transaction_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_refunds');
    }
};
