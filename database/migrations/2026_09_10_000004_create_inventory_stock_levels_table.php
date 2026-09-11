<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Non-serialized stock, one row per (sku, shop) (DBDD §14.4/§33). There
 * is deliberately no `available` column — it is always computed as
 * on_hand - reserved (DBDD §5.2, BLD §2.1), never persisted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stock_levels', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->foreignId('sku_id')
                ->constrained('inventory_skus')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->foreignId('shop_id')
                ->constrained('shops')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->unsignedInteger('on_hand');
            $table->unsignedInteger('reserved');

            $table->unsignedInteger('version')->default(0);

            $table->timestamps();

            $table->unique(
                ['sku_id', 'shop_id'],
                'inventory_stock_levels_sku_shop_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_levels');
    }
};
