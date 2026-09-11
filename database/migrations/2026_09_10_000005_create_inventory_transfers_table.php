<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inter-shop transfer lifecycle (DBDD §14.5, INV-BR-11/13). For a
 * serialized transfer, inventory_item_id is populated; for a bulk
 * non-serialized transfer, quantity is populated.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transfers', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->foreignId('sku_id')
                ->constrained('inventory_skus')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->foreignId('inventory_item_id')
                ->nullable()
                ->constrained('inventory_items')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->unsignedInteger('quantity')->nullable();

            $table->foreignId('from_shop_id')
                ->constrained('shops')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->foreignId('to_shop_id')
                ->constrained('shops')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->foreignId('initiated_by_staff_id')
                ->constrained('staff')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->foreignId('received_by_staff_id')
                ->nullable()
                ->constrained('staff')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->enum('status', ['in_transit', 'completed', 'cancelled']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transfers');
    }
};
