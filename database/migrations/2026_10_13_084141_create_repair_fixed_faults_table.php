<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_fixed_faults', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->foreignId('repair_job_id')->constrained('repair_jobs')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('diagnosis_case_fault_id')->nullable()->constrained('diagnosis_case_faults')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('device_problem_tag_id')->nullable()->constrained('device_problem_tags')->nullOnDelete()->cascadeOnUpdate();
            $table->string('label_snapshot', 100);
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by_staff_id')->nullable()->constrained('staff')->nullOnDelete()->restrictOnUpdate();
            $table->timestamps();

            $table->index('repair_job_id', 'repair_fixed_faults_job_idx');
            $table->unique(['repair_job_id', 'label_snapshot'], 'repair_fixed_faults_job_label_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_fixed_faults');
    }
};
