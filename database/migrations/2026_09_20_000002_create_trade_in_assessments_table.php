<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DBDD §15.4. `related_checkout_id` is a Sales-module ID reference
 * without a cross-module FK. `assessed_by_staff_id` isn't in DBDD's
 * literal column list, but recording who assessed the value is
 * required to enforce TRADE-BR-05 (the approver must differ from the
 * assessor) at all.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_in_assessments', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->unsignedBigInteger('related_checkout_id')->nullable();
            $table->json('device_description');
            $table->bigInteger('assessed_value_minor')->nullable();

            $table->enum('resolution_state', [
                'submitted',
                'assessed',
                'approved',
                'rejected',
                'applied',
            ])->default('submitted');

            $table->foreignId('assessed_by_staff_id')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete()
                ->restrictOnUpdate();

            $table->foreignId('approved_by_staff_id')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete()
                ->restrictOnUpdate();

            $table->timestamps();

            $table->index('customer_id', 'trade_in_assessments_customer_idx');
            $table->index('resolution_state', 'trade_in_assessments_state_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_in_assessments');
    }
};
