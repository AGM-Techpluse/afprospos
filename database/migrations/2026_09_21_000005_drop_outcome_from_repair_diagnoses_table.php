<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** REP-BR-29: the outcome classification now lives on `repair_diagnosis_conclusions`, its own distinct fact — never on a per-component test observation. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_diagnoses', function (Blueprint $table): void {
            $table->dropColumn('outcome');
        });
    }

    public function down(): void
    {
        Schema::table('repair_diagnoses', function (Blueprint $table): void {
            $table->enum('outcome', ['repairable', 'unrepairable', 'requires_further_assessment'])->nullable()->after('notes');
        });
    }
};
