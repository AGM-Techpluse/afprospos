<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A single priced adjustment against a checkout (DBDD §13.3) — implements
 * MKT-BR-07's deterministic stacking order via `applied_order` and
 * MKT-BR-08's per-component traceability. `source_id` is an opaque
 * cross-module reference (promotion/voucher/trade-in/credit source) —
 * no FK, since none of those owning modules exist yet (Phase 4 plan's
 * resolved ambiguity #7).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_checkout_adjustments', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->foreignId('sales_checkout_id')
                ->constrained('sales_checkouts')
                ->cascadeOnDelete()
                ->restrictOnUpdate();

            $table->enum('type', ['promotion', 'referral_voucher', 'trade_in_credit', 'store_credit']);
            $table->unsignedBigInteger('source_id');
            $table->bigInteger('amount_minor');
            $table->unsignedTinyInteger('applied_order');

            $table->timestamps();

            $table->index('sales_checkout_id', 'sales_checkout_adjustments_checkout_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_checkout_adjustments');
    }
};
