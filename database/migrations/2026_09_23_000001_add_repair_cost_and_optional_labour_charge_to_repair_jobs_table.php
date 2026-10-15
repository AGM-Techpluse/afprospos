<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `labour_charge_minor` was required at intake and, in practice, treated
 * as the repair's price — but the actual price is only knowable after
 * diagnosis, once a technician/manager estimates it and the customer
 * agrees. `repair_cost_minor` is the real, post-diagnosis price; labour
 * charge stays but becomes optional, kept only for a possible future
 * per-repair technician-commission use case.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_jobs', function (Blueprint $table): void {
            $table->bigInteger('repair_cost_minor')->nullable()->after('labour_charge_minor');
        });

        Schema::table('repair_jobs', function (Blueprint $table): void {
            $table->bigInteger('labour_charge_minor')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('repair_jobs', function (Blueprint $table): void {
            $table->bigInteger('labour_charge_minor')->nullable(false)->change();
        });

        Schema::table('repair_jobs', function (Blueprint $table): void {
            $table->dropColumn('repair_cost_minor');
        });
    }
};
