<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table): void {
            $table->string('serial_number', 80)->nullable()->after('imei');
            $table->unique('serial_number', 'inventory_items_serial_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table): void {
            $table->dropUnique('inventory_items_serial_number_unique');
            $table->dropColumn('serial_number');
        });
    }
};
