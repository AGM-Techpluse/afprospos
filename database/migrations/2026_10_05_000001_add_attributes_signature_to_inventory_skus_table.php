<?php

declare(strict_types=1);

use Domain\Inventory\Domain\Services\AttributeSignature;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Serialized and variant SKUs are matched on product + attributes + serialized
 * flag (INV-BR-08/18). The signature makes that an indexed lookup, and existing
 * rows are backfilled with the same canonical form the application writes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_skus', function (Blueprint $table): void {
            $table->string('attributes_signature', 64)->nullable()->after('attributes');
            $table->index(['product_id', 'is_serialized', 'attributes_signature'], 'inventory_skus_variant_lookup');
        });

        DB::table('inventory_skus')->orderBy('id')->chunkById(200, function ($skus): void {
            foreach ($skus as $sku) {
                $attributes = json_decode((string) $sku->attributes, true) ?? [];

                DB::table('inventory_skus')
                    ->where('id', $sku->id)
                    ->update(['attributes_signature' => AttributeSignature::of($attributes)]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_skus', function (Blueprint $table): void {
            $table->dropIndex('inventory_skus_variant_lookup');
            $table->dropColumn('attributes_signature');
        });
    }
};
