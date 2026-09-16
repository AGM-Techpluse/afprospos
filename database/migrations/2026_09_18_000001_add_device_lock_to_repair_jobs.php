<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DBDD Amendment (Repair module, Phase 6). Stores the customer's device
 * unlock credential — a passcode or a 3x3 pattern-lock sequence (e.g.
 * "1-5-9") — so a technician can actually get into the device to diagnose
 * it. This is customer credential data, not ordinary business data:
 * `device_lock_value` is encrypted at rest (RepairJobRecord's `encrypted`
 * cast) and `RepairJobDetailQuery::find()` only ever returns the
 * decrypted value to the job's assigned technician or the Shop Owner —
 * everyone else gets `device_lock_present: true` with no value, so the
 * UI can show "set, hidden" rather than looking broken. It's cleared
 * (not just left encrypted) once the device is released back to the
 * customer (Collection's release flow), since there's no legitimate
 * reason to keep holding it after that point.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_jobs', function (Blueprint $table): void {
            $table->enum('device_lock_type', ['none', 'code', 'pattern'])->default('none')->after('device_imei_serial');
            $table->text('device_lock_value')->nullable()->after('device_lock_type');
        });
    }

    public function down(): void
    {
        Schema::table('repair_jobs', function (Blueprint $table): void {
            $table->dropColumn(['device_lock_type', 'device_lock_value']);
        });
    }
};
