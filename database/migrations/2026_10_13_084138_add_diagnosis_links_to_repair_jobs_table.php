<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_jobs', function (Blueprint $table): void {
            $table->foreignId('diagnosis_case_id')
                ->nullable()
                ->after('id')
                ->constrained('diagnosis_cases')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('reopened_from_repair_job_id')
                ->nullable()
                ->after('diagnosis_case_id')
                ->constrained('repair_jobs')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->index('diagnosis_case_id', 'repair_jobs_diagnosis_case_idx');
        });
    }

    public function down(): void
    {
        Schema::table('repair_jobs', function (Blueprint $table): void {
            $table->dropForeign(['diagnosis_case_id']);
            $table->dropForeign(['reopened_from_repair_job_id']);
            $table->dropIndex('repair_jobs_diagnosis_case_idx');
            $table->dropColumn(['diagnosis_case_id', 'reopened_from_repair_job_id']);
        });
    }
};
