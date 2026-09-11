<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One reserved line item on a checkout (DBDD §13.2). `inventory_item_id`
 * and `sku_id` are cross-module IDs, never foreign keys (CPNC §4.3) —
 * Sales must reach Inventory only through InventoryReservationService.
 * `unit_price_minor` is a required price snapshot (DBDD §28) so a later
 * catalog price change never rewrites an existing checkout/invoice.
 * `warranty_policy_id` is finalized in the schema but stays unused until
 * the Warranty module (Phase 7) exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_checkout_items', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->foreignId('sales_checkout_id')
                ->constrained('sales_checkouts')
                ->cascadeOnDelete()
                ->restrictOnUpdate();

            $table->unsignedBigInteger('inventory_item_id')->nullable();
            $table->unsignedBigInteger('sku_id');

            $table->unsignedInteger('quantity');
            $table->bigInteger('unit_price_minor');

            $table->unsignedBigInteger('warranty_policy_id')->nullable();

            $table->timestamps();

            $table->index('sales_checkout_id', 'sales_checkout_items_checkout_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_checkout_items');
    }
};
