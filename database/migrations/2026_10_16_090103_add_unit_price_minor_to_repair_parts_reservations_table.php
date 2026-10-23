<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshots the SKU's selling price at the moment a part is reserved, so a
 * later price change in Inventory never silently rewrites what an existing
 * reservation (and the quote/invoice built from it) already showed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_parts_reservations', function (Blueprint $table): void {
            $table->unsignedBigInteger('unit_price_minor')->after('quantity')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('repair_parts_reservations', function (Blueprint $table): void {
            $table->dropColumn('unit_price_minor');
        });
    }
};
