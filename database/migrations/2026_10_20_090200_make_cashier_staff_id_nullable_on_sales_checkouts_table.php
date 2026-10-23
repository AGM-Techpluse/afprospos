<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A customer- or guest-originated checkout (the storefront cart/reservation
 * flow) has no cashier at all — mirrors `customer_id`'s existing
 * nullability for a walk-in sale, just the other way round.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_checkouts', function (Blueprint $table): void {
            $table->dropForeign(['cashier_staff_id']);
        });

        Schema::table('sales_checkouts', function (Blueprint $table): void {
            $table->unsignedBigInteger('cashier_staff_id')->nullable()->change();
        });

        Schema::table('sales_checkouts', function (Blueprint $table): void {
            $table->foreign('cashier_staff_id')
                ->references('id')->on('staff')
                ->restrictOnDelete()
                ->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('sales_checkouts', function (Blueprint $table): void {
            $table->dropForeign(['cashier_staff_id']);
        });

        Schema::table('sales_checkouts', function (Blueprint $table): void {
            $table->unsignedBigInteger('cashier_staff_id')->nullable(false)->change();
        });

        Schema::table('sales_checkouts', function (Blueprint $table): void {
            $table->foreign('cashier_staff_id')
                ->references('id')->on('staff')
                ->restrictOnDelete()
                ->restrictOnUpdate();
        });
    }
};
