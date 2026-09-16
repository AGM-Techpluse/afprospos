<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DBDD Amendment (Repair module, Phase 6).
 *
 * The finalized data model (DBDD §11.1 `repair_jobs`) has no field for what
 * the customer says is wrong with the device at intake, nor for what the
 * technician actually did to close the job out. `repair_diagnoses.notes`
 * is the technician's own per-component finding recorded during diagnosis
 * — a different thing from either of these, and not a substitute for
 * either: `reported_issue` is the customer's stated complaint, captured
 * before diagnosis even starts; `resolution_notes` is a free-text summary
 * of the work performed, recorded at completion so a parts-less repair
 * (reflow, a software fix, reseating a cable) still leaves a record of
 * what was done, since Repair Part Reservations only exist when a part
 * was actually used.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_jobs', function (Blueprint $table): void {
            $table->text('reported_issue')->nullable()->after('device_model');
            $table->text('resolution_notes')->nullable()->after('unrepairable_settlement_state');
        });
    }

    public function down(): void
    {
        Schema::table('repair_jobs', function (Blueprint $table): void {
            $table->dropColumn(['reported_issue', 'resolution_notes']);
        });
    }
};
