<?php

declare(strict_types=1);

namespace App\Console\Commands\Notifications;

use Domain\Notifications\Domain\Repositories\NotificationEventRepository;
use Domain\Notifications\Infrastructure\Jobs\DispatchNotificationJob;
use Illuminate\Console\Command;

/**
 * CPNC §2.2 shape (mirrors Sales\ExpireCheckouts): fetches the retryable
 * IDs via the repository, then re-dispatches one job per ID. A safety net
 * for jobs lost at the queue level (worker restart, `failed_jobs`) — the
 * primary retry path is DispatchNotificationJob's own backoff().
 */
final class RetryFailedNotifications extends Command
{
    protected $signature = 'afprospos:notifications:retry-failed';

    protected $description = 'Re-dispatches queued notification events whose delivery attempts are below the configured maximum (NOTIF-BR-06 safety net).';

    public function __construct(private readonly NotificationEventRepository $notificationEvents)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $maxAttempts = (int) config('afprospos.notifications.max_delivery_attempts', 3);
        $ids = $this->notificationEvents->findRetryable($maxAttempts);

        foreach ($ids as $id) {
            DispatchNotificationJob::dispatch($id->value);
        }

        $this->info(count($ids).' notification event(s) re-dispatched.');

        return self::SUCCESS;
    }
}
