<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Adds a non-terminal status for a transaction that's had some, but not all, of its amount refunded — see PaymentTransitionPolicy. */
return new class extends Migration
{
    private const STATUSES = ['pending', 'payment_pending_confirmation', 'confirmed', 'disputed', 'partially_refunded', 'refunded', 'exception'];

    public function up(): void
    {
        Schema::table('payments_transactions', function (Blueprint $table): void {
            $table->enum('status', self::STATUSES)->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments_transactions', function (Blueprint $table): void {
            $table->enum('status', array_values(array_diff(self::STATUSES, ['partially_refunded'])))->default('pending')->change();
        });
    }
};
