<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DBDD §15.3. `sale_id` is a cross-module ID by value only -- no FK --
 * validated through Sales' published SaleLookup contract at write time
 * (CPNC §0.2), same as warranty_claims' originating references.
 * `refund_transaction_id` isn't in DBDD's literal column list but fills
 * the same gap `warranty_claims.remedy_reference_id` already fills for
 * Claims -- recording which payment transaction a refund executed
 * against.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_requests', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->unsignedBigInteger('sale_id');

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->enum('resolution_state', [
                'requested',
                'under_assessment',
                'approved',
                'denied',
                'resolved',
            ])->default('requested');

            $table->dateTime('return_window_expires_at');
            $table->string('denial_reason')->nullable();

            $table->foreignId('override_approved_by_staff_id')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete()
                ->restrictOnUpdate();

            $table->unsignedBigInteger('refund_transaction_id')->nullable();

            $table->timestamps();

            $table->index('customer_id', 'return_requests_customer_idx');
            $table->index('sale_id', 'return_requests_sale_idx');
            $table->index('resolution_state', 'return_requests_state_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_requests');
    }
};
