<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshots the commission amount actually credited for this fixed
 * fault — the technician can now edit it per repair (CompleteRepairHandler
 * uses this value instead of a fresh device_problem_tags lookup when
 * given), mirroring this session's own price-snapshot precedent
 * (repair_parts_reservations.unit_price_minor) rather than re-deriving
 * from a catalog value that might change later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_fixed_faults', function (Blueprint $table): void {
            $table->unsignedBigInteger('commission_amount_minor')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('repair_fixed_faults', function (Blueprint $table): void {
            $table->dropColumn('commission_amount_minor');
        });
    }
};
