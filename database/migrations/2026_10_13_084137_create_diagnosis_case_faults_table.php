<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnosis_case_faults', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->foreignId('diagnosis_case_id')->constrained('diagnosis_cases')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('device_problem_tag_id')->nullable()->constrained('device_problem_tags')->nullOnDelete()->cascadeOnUpdate();
            $table->string('label_snapshot', 100);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('diagnosis_case_id', 'diagnosis_case_faults_case_idx');
            $table->unique(['diagnosis_case_id', 'label_snapshot'], 'diagnosis_case_faults_case_label_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosis_case_faults');
    }
};
