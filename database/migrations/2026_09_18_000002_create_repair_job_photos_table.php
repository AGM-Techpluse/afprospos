<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DBDD Amendment (Repair module, Phase 6). Photo evidence of the device's
 * condition -- taken at intake, or any later point in the job's lifecycle
 * (e.g. after a repair, before release) -- stored on the `public` disk so
 * they're servable by URL without a signed-route detour. Immutable once
 * uploaded (no edit, only delete) -- no `updated_at`, same
 * "no updated_at signals append-only" convention as
 * `repair_diagnoses`/`audit_logs`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_job_photos', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->foreignId('repair_job_id')->constrained('repair_jobs')->cascadeOnDelete()->restrictOnUpdate();
            $table->string('path', 255);
            $table->string('caption', 150)->nullable();
            $table->foreignId('uploaded_by_staff_id')->constrained('staff')->restrictOnDelete()->restrictOnUpdate();
            $table->timestamp('created_at')->useCurrent();

            $table->index('repair_job_id', 'repair_job_photos_job_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_job_photos');
    }
};
