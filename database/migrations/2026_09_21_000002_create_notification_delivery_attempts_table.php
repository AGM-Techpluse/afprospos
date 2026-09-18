<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DBDD §20.2. Append-only — mirrors audit_logs' immutability discipline (no updated_at, never update()'d). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_delivery_attempts', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->foreignId('notification_event_id')
                ->constrained('notification_events')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->enum('channel', ['whatsapp', 'email', 'in_app', 'sms']);
            $table->string('provider');
            $table->unsignedInteger('attempt_number');

            $table->enum('status', ['sent', 'delivered', 'failed_transient', 'failed_permanent']);
            $table->text('provider_response')->nullable();

            $table->timestamp('created_at')->nullable();

            $table->index(['notification_event_id', 'attempt_number'], 'notification_attempts_event_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_delivery_attempts');
    }
};
