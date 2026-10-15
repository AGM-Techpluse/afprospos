<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_fixed_faults', function (Blueprint $table): void {
            $table->foreignId('common_component_id')->nullable()->after('device_problem_tag_id')
                ->constrained('common_components')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('repair_fixed_faults', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('common_component_id');
        });
    }
};
