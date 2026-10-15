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
        Schema::table('repair_jobs', function (Blueprint $table): void {
            $table->unsignedBigInteger('failure_service_charge_minor')->nullable()->after('resolution_notes');
            $table->unsignedBigInteger('failure_refund_due_minor')->nullable()->after('failure_service_charge_minor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repair_jobs', function (Blueprint $table): void {
            $table->dropColumn(['failure_service_charge_minor', 'failure_refund_due_minor']);
        });
    }
};
