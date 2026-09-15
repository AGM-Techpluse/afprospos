<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 (Payments): every Sale is now created only after a
 * PaymentTransaction has been successfully initiated
 * (CreateSaleFromPaidCheckoutHandler), so the placeholder nullable column
 * from Phase 4 can become a real, always-populated reference — resolving
 * the DBDD §13.4 vs. migration nullability inconsistency in favor of
 * non-nullable. Still no foreign key: Payments stays a standalone bounded
 * context (DBDD §16.1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_sales', function (Blueprint $table): void {
            $table->unsignedBigInteger('payment_transaction_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('sales_sales', function (Blueprint $table): void {
            $table->unsignedBigInteger('payment_transaction_id')->nullable()->change();
        });
    }
};
