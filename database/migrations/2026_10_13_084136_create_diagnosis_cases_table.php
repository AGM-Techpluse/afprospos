<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnosis_cases', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('device_model_id')->nullable()->constrained('device_models')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('diagnosis_technician_staff_id')->nullable()->constrained('staff')->nullOnDelete()->restrictOnUpdate();
            $table->foreignId('created_by_staff_id')->constrained('staff')->restrictOnDelete()->restrictOnUpdate();

            $table->string('device_make', 100);
            $table->string('device_model', 150);
            $table->text('reported_issue')->nullable();
            $table->string('device_imei_serial', 50)->nullable();
            $table->string('device_lock_type', 20)->default('none');
            $table->text('device_lock_value')->nullable();
            $table->enum('status', ['received', 'diagnosed', 'called_up', 'declined'])->default('received');
            $table->enum('outcome', ['repairable', 'unrepairable', 'requires_further_assessment'])->nullable();
            $table->text('diagnosis_notes')->nullable();
            $table->bigInteger('diagnosis_fee_minor')->nullable();
            $table->timestamp('diagnosed_at')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'status'], 'diagnosis_cases_shop_status_idx');
            $table->index(['customer_id', 'created_at'], 'diagnosis_cases_customer_created_idx');
            $table->index('diagnosis_technician_staff_id', 'diagnosis_cases_technician_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosis_cases');
    }
};
