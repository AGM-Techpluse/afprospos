<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A finalized sale, separate from the mutable checkout (DBDD §13.4) —
 * deliberately no `updated_at`. `payment_transaction_id` is reserved for
 * a genuine Payments-module ID (Phase 5) and stays null until then;
 * `payment_method`/`payment_reference` are additive columns beyond
 * DBDD's plain column list, covering BRD PAY-05's reconciliation
 * requirement for the cash/POS-terminal/bank-transfer capture Phase 4
 * itself owns (Phase 4 plan's resolved ambiguity #2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_sales', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->foreignId('sales_checkout_id')
                ->constrained('sales_checkouts')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->foreignId('shop_id')
                ->constrained('shops')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete()
                ->restrictOnUpdate();

            $table->foreignId('cashier_staff_id')
                ->constrained('staff')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->bigInteger('total_minor');

            $table->unsignedBigInteger('payment_transaction_id')->nullable();
            $table->enum('payment_method', ['cash', 'pos_terminal', 'bank_transfer', 'in_app']);
            $table->string('payment_reference')->nullable();

            $table->string('invoice_number', 50)->unique();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['shop_id', 'created_at'], 'sales_sales_shop_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_sales');
    }
};
