<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Storefront-facing copy — a product has none today, which leaves a public product page with no text at all. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_products', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_products', function (Blueprint $table): void {
            $table->dropColumn('description');
        });
    }
};
