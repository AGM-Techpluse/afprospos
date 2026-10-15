<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The flat technician commission paid for a fault tied to this tag, ticked as actually fixed at repair completion. Null means not commissionable. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_problem_tags', function (Blueprint $table): void {
            $table->unsignedBigInteger('commission_amount_minor')->nullable()->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('device_problem_tags', function (Blueprint $table): void {
            $table->dropColumn('commission_amount_minor');
        });
    }
};
