<?php

declare(strict_types=1);

namespace Domain\Notifications\Infrastructure\Jobs;

use Domain\Notifications\Application\Services\NotificationEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** NOTIF-BR-02: runs independently of the originating business transaction — RequestNotificationHandler only dispatches this after its own Atomic::run() has already committed. NOTIF-BR-06's "increasing retry intervals" is the queue's own backoff, not a business-logic loop. */
final class DispatchNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public function __construct(public readonly int $notificationEventId) {}

    public function backoff(): array
    {
        return config('afprospos.notifications.retry_backoff_seconds', [60, 300, 900]);
    }

    public function handle(NotificationEngine $engine): void
    {
        $engine->process($this->notificationEventId, $this->attempts());
    }
}
