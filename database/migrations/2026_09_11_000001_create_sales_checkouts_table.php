<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A mutable, in-progress cart holding an inventory reservation while
 * payment is pending (DBDD §13.1, BLD §4.1). `status` is the literal
 * 4-value enum DBDD finalizes — "payment-pending" and "converted" from
 * the Implementation Plan's prose map onto `open` and `paid`
 * respectively (see Phase 4 plan's resolved ambiguity #1); there is no
 * separate payment-pending checkout state, that concept belongs to the
 * future Payments module (ADD §11.3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_checkouts', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

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

            $table->enum('status', ['open', 'paid', 'expired', 'cancelled'])->default('open');
            $table->dateTime('reservation_expires_at');

            $table->bigInteger('subtotal_minor')->default(0);
            $table->bigInteger('discount_minor')->default(0);
            $table->bigInteger('total_minor')->default(0);

            $table->unsignedBigInteger('payment_transaction_id')->nullable();

            $table->timestamps();

            $table->index(['status', 'reservation_expires_at'], 'sales_checkouts_status_expiry_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_checkouts');
    }
};
