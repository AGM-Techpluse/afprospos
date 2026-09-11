<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Business-wide unique SKU (DBDD §14.2, INV-BR-08): a phone transferred
 * between shops never needs a second SKU, so this table has no shop_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_skus', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->foreignId('product_id')
                ->constrained('inventory_products')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->string('sku_code', 50)->unique();
            $table->json('attributes');
            $table->boolean('is_serialized');
            $table->bigInteger('cost_price_minor');
            $table->decimal('markup_percent', 5, 2);
            $table->bigInteger('selling_price_minor');
            $table->boolean('selling_price_overridden')->default(false);
            $table->unsignedInteger('low_stock_threshold')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_skus');
    }
};
