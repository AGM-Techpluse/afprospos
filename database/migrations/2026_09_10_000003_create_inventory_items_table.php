<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Serialized inventory (DBDD §14.3/§32). IMEI is globally unique across
 * the business, not merely unique within a shop.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->unsignedBigInteger('sku_id');

            $table->string('imei', 20)->unique();

            // Shared Kernel FK: valid database relationship.
            $table->foreignId('current_shop_id')
                ->constrained('shops')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->enum('condition', [
                'new',
                'used_grade_a',
                'used_grade_b',
                'used_grade_c',
                'refurbished',
            ]);

            $table->enum('status', [
                'available',
                'reserved',
                'sold',
                'transferring',
            ]);

            $table->enum('reserved_by_type', [
                'repair',
                'checkout',
            ])->nullable();

            /*
             * Application-level cross-module ID. Deliberately NOT a
             * foreign key because this column may point into Repair or
             * Sales depending on reserved_by_type (DBDD §32).
             */
            $table->unsignedBigInteger('reserved_by_id')->nullable();

            // Secondary concurrency guard (DBDD §5.4) alongside SELECT ... FOR UPDATE.
            $table->unsignedInteger('version')->default(0);

            $table->timestamps();

            $table->foreign('sku_id')
                ->references('id')
                ->on('inventory_skus')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->index(
                ['current_shop_id', 'status'],
                'inventory_items_shop_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
