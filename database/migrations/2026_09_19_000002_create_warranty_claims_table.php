<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DBDD §15.2. `originating_sale_id`/`originating_repair_job_id`/
 * `inventory_item_id` are cross-module IDs by value only -- "There is
 * no cross-module FK on the originating/remedy references" (DBDD's own
 * words) -- validated instead through Sales'/Repair's published
 * SaleLookup/RepairJobLookup contracts at write time (CPNC §0.2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranty_claims', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->foreignId('warranty_policy_id')
                ->constrained('warranty_policies')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->unsignedBigInteger('originating_sale_id')->nullable();
            $table->unsignedBigInteger('originating_repair_job_id')->nullable();

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->unsignedBigInteger('inventory_item_id')->nullable();

            $table->enum('resolution_state', [
                'submitted',
                'under_assessment',
                'eligible',
                'not_eligible',
                'remedy_selected',
                'resolved',
            ])->default('submitted');

            $table->text('assessment_notes')->nullable();
            $table->enum('selected_remedy', ['repair', 'replace', 'refund', 'exchange'])->nullable();
            $table->unsignedBigInteger('remedy_reference_id')->nullable();

            $table->timestamps();

            $table->index('customer_id', 'warranty_claims_customer_idx');
            $table->index('resolution_state', 'warranty_claims_state_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_claims');
    }
};
