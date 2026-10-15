<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The flat staff commission paid when this SKU is sold, earned by the cashier at sale finalization. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_skus', function (Blueprint $table): void {
            $table->boolean('is_commissionable')->default(false)->after('low_stock_threshold');
            $table->unsignedBigInteger('commission_amount_minor')->nullable()->after('is_commissionable');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_skus', function (Blueprint $table): void {
            $table->dropColumn(['is_commissionable', 'commission_amount_minor']);
        });
    }
};
