<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MKT-BR-15: every checkout discount/adjustment requires Shop Owner
 * approval before the checkout can be paid. Approval state lives on the
 * adjustment itself (mirrors `trade_in_assessments`' own approval fields)
 * since a checkout holds at most one live adjustment (MKT-BR-06).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_checkout_adjustments', function (Blueprint $table): void {
            $table->enum('approval_status', ['pending', 'approved'])->default('pending')->after('applied_order');
            $table->foreignId('approved_by_staff_id')->nullable()->after('approval_status')
                ->constrained('staff')->nullOnDelete()->restrictOnUpdate();
            $table->timestamp('approved_at')->nullable()->after('approved_by_staff_id');
        });
    }

    public function down(): void
    {
        Schema::table('sales_checkout_adjustments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('approved_by_staff_id');
            $table->dropColumn(['approval_status', 'approved_at']);
        });
    }
};
