<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DBDD Amendment (Repair module, Phase 6). Adds the device's own
 * IMEI/serial to intake, and a many-to-many link from a repair job to the
 * problem tags selected during intake (a job can have more than one, e.g.
 * "screen" + "battery"). `label_snapshot` freezes the tag's label at
 * selection time so a later rename/delete in the device catalog never
 * changes what an already-created repair job displays (same reasoning as
 * `actor_role_snapshot` elsewhere in this codebase).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_jobs', function (Blueprint $table): void {
            $table->string('device_imei_serial', 50)->nullable()->after('reported_issue');
        });

        Schema::create('repair_job_problem_tags', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->foreignId('repair_job_id')->constrained('repair_jobs')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('device_problem_tag_id')->nullable()->constrained('device_problem_tags')->nullOnDelete()->cascadeOnUpdate();
            $table->string('label_snapshot', 100);
            $table->timestamps();

            $table->index('repair_job_id', 'repair_job_problem_tags_job_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_job_problem_tags');

        Schema::table('repair_jobs', function (Blueprint $table): void {
            $table->dropColumn('device_imei_serial');
        });
    }
};
