<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DBDD §20.1. `source_id`/`recipient_id` are IDs resolved by application
 * services, not FKs (polymorphic-by-convention, per the DBDD's own note).
 * `payload` and `read_at` aren't in DBDD's literal column list but fill
 * the same kind of gap `return_requests.refund_transaction_id` already
 * filled for Warranty: `payload` snapshots the triggering event's own
 * facts so notification content reflects what was true at event time,
 * not a live cross-module lookup at send/render time; `read_at` gives
 * the in-app channel (NOTIF-BR-18: "an internal, always-available
 * notification record") a real read/unread lifecycle. `updated_at` is
 * added alongside `created_at` because, unlike an audit log, this row
 * genuinely mutates (status transitions, read_at) over its lifetime.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_events', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->string('event_type');
            $table->string('source_module');
            $table->unsignedBigInteger('source_id');

            $table->enum('recipient_type', ['customer', 'staff']);
            $table->unsignedBigInteger('recipient_id');

            $table->enum('category', ['transactional', 'operational', 'security', 'marketing']);
            $table->json('payload')->nullable();

            $table->enum('status', ['queued', 'delivered', 'failed_exhausted'])->default('queued');
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->index(['recipient_type', 'recipient_id'], 'notification_events_recipient_idx');
            $table->index('status', 'notification_events_status_idx');
            $table->index(['source_module', 'source_id'], 'notification_events_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_events');
    }
};
