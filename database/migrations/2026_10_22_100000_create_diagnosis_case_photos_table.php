<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Photo evidence of the device's condition captured at diagnosis intake
 * — mirrors repair_job_photos exactly (same "no updated_at signals
 * append-only" convention). When a diagnosed case becomes a real repair
 * job, these are copied into repair_job_photos (same stored file, no
 * re-upload) so they keep appearing in the repair's own photo gallery.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnosis_case_photos', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->foreignId('diagnosis_case_id')->constrained('diagnosis_cases')->cascadeOnDelete()->restrictOnUpdate();
            $table->string('path', 255);
            $table->string('caption', 150)->nullable();
            $table->foreignId('uploaded_by_staff_id')->constrained('staff')->restrictOnDelete()->restrictOnUpdate();
            $table->timestamp('created_at')->useCurrent();

            $table->index('diagnosis_case_id', 'diagnosis_case_photos_case_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosis_case_photos');
    }
};
