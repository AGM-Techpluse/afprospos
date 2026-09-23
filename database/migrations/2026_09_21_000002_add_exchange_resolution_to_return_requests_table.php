<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WAR-BR-13: an approved Return may resolve as an Exchange instead of a
 * Refund — the sale's full paid value is applied as a `store_credit`
 * adjustment on a new checkout rather than money moving back through the
 * original payment method. `exchange_checkout_id` is an opaque
 * cross-module reference, no FK, matching this table's existing
 * `sale_id` convention.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('return_requests', function (Blueprint $table): void {
            $table->enum('resolution_type', ['refund', 'exchange'])->nullable()->after('resolution_state');
            $table->unsignedBigInteger('exchange_checkout_id')->nullable()->after('refund_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('return_requests', function (Blueprint $table): void {
            $table->dropColumn(['resolution_type', 'exchange_checkout_id']);
        });
    }
};
