<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DBDD §11.3. `inventory_item_id`/`sku_id` are cross-module IDs,
 * deliberately NOT foreign keys — Inventory is another bounded context.
 * `status` here is this reservation's own bookkeeping (has the part been
 * physically fitted yet?), separate from Inventory's own item-level
 * status — consumption happens at `installed`, not at `reserved`
 * (Phase 6 scope decision: reservation is never silent consumption).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_parts_reservations', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->foreignId('repair_job_id')
                ->constrained('repair_jobs')
                ->cascadeOnDelete()
                ->restrictOnUpdate();

            $table->unsignedBigInteger('inventory_item_id')->nullable();
            $table->unsignedBigInteger('sku_id');
            $table->unsignedInteger('quantity');

            $table->enum('status', ['reserved', 'installed', 'released'])->default('reserved');

            $table->timestamps();

            $table->index('repair_job_id', 'repair_parts_reservations_job_idx');
            $table->index('sku_id', 'repair_parts_reservations_sku_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_parts_reservations');
    }
};
