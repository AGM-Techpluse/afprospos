<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Frontdesk records how the completion payment was actually taken so
 * `DecideRepairPaymentApprovalHandler` can initiate the real
 * `payments_transactions` row (purpose `repair_final_payment`) once Shop
 * Owner approval lands — mirrors `AuthorizeRepairCommand::downPaymentMethod`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_payment_approvals', function (Blueprint $table): void {
            $table->enum('method', ['cash', 'pos_terminal', 'bank_transfer'])
                ->default('cash')
                ->after('amount_minor');
        });
    }

    public function down(): void
    {
        Schema::table('repair_payment_approvals', function (Blueprint $table): void {
            $table->dropColumn('method');
        });
    }
};
