<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Carries the device model from diagnosis-case intake into the repair job, where model-scoped commission is actually credited. Previously missing entirely — dropped on conversion. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_jobs', function (Blueprint $table): void {
            $table->foreignId('device_model_id')->nullable()->after('device_model')
                ->constrained('device_models')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('repair_jobs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('device_model_id');
        });
    }
};
