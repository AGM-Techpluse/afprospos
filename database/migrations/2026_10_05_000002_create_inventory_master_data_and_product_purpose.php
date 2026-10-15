<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-managed inventory master data: product categories, base units, and
 * suppliers — one table, distinguished by `kind`, so adding a fourth list later
 * needs no new table. Products reference categories and units by name (the
 * existing product identity is brand+model+category) and suppliers by id.
 *
 * `purpose` says what a product is used for: sold to customers (`sale`), fitted
 * in repairs (`repair_part`), or both (e.g. a screen that is sold and fitted).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_master_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('kind', 20);
            $table->string('name', 120);
            $table->string('contact_phone', 40)->nullable();
            $table->string('contact_email', 190)->nullable();
            $table->timestamps();

            $table->unique(['kind', 'name'], 'inventory_master_entries_kind_name_unique');
        });

        // Starter lists so the product form is usable immediately; the admin edits them from Settings.
        $now = now();
        DB::table('inventory_master_entries')->insert([
            ['kind' => 'unit', 'name' => 'pcs', 'created_at' => $now, 'updated_at' => $now],
            ['kind' => 'unit', 'name' => 'pack', 'created_at' => $now, 'updated_at' => $now],
            ['kind' => 'unit', 'name' => 'box', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Categories already in use become master entries, so existing products keep a valid category.
        $existingCategories = DB::table('inventory_products')->distinct()->orderBy('category')->pluck('category');
        $seedCategories = array_unique(array_merge(['Phones', 'Tablets', 'Accessories', 'Parts'], $existingCategories->all()));

        foreach ($seedCategories as $name) {
            if (trim((string) $name) === '') {
                continue;
            }

            DB::table('inventory_master_entries')->insertOrIgnore([
                'kind' => 'category', 'name' => trim((string) $name), 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        Schema::table('inventory_products', function (Blueprint $table): void {
            $table->string('unit', 120)->default('pcs')->after('category');
            $table->string('purpose', 20)->default('sale')->after('unit');
            $table->foreignId('supplier_id')->nullable()->after('purpose')
                ->constrained('inventory_master_entries')->nullOnDelete();
        });

        Schema::table('shops', function (Blueprint $table): void {
            // A warehouse holds stock and receives/sends transfers only: no sales, no repairs.
            $table->boolean('is_warehouse')->default(false)->after('sku_prefix_code');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table): void {
            $table->dropColumn('is_warehouse');
        });

        Schema::table('inventory_products', function (Blueprint $table): void {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['unit', 'purpose', 'supplier_id']);
        });

        Schema::dropIfExists('inventory_master_entries');
    }
};
