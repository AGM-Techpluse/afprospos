<?php

declare(strict_types=1);

namespace Domain\Notifications\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Notifications\Application\Commands\RecordDeliveryAttemptCommand;
use Domain\Notifications\Domain\Repositories\NotificationEventRepository;
use Domain\Notifications\Domain\ValueObjects\NotificationEventId;
use Domain\Notifications\Infrastructure\Persistence\Eloquent\NotificationDeliveryAttemptRecord;

/** NOTIF-BR-07: classifies the outcome and transitions the NotificationEvent. Delivered -> terminal. Transient with attempts remaining -> stays `queued` (DispatchNotificationJob's own backoff will retry). Transient with attempts exhausted, or any permanent failure -> `failed_exhausted` (NOTIF-BR-12). */
final class RecordDeliveryAttemptHandler
{
    public function __construct(
        private readonly NotificationEventRepository $notificationEvents,
        private readonly Atomic $atomic,
    ) {}

    public function handle(RecordDeliveryAttemptCommand $command, int $maxAttempts): void
    {
        $this->atomic->run(function () use ($command, $maxAttempts): void {
            NotificationDeliveryAttemptRecord::query()->create([
                'notification_event_id' => $command->notificationEventId,
                'channel' => $command->channel,
                'provider' => $command->provider,
                'attempt_number' => $command->attemptNumber,
                'status' => $command->status,
                'provider_response' => $command->providerResponse,
            ]);

            if ($command->status !== 'delivered' && $command->status !== 'failed_permanent' && $command->attemptNumber < $maxAttempts) {
                // Transient failure, attempts remain — leave the event `queued` for the job's own backoff to retry.
                return;
            }

            $id = new NotificationEventId($command->notificationEventId);
            $notificationEvent = $this->notificationEvents->lockForUpdate($id);

            if ($notificationEvent->status() !== 'queued') {
                return;
            }

            if ($command->status === 'delivered') {
                $notificationEvent->markDelivered();
            } else {
                $notificationEvent->markFailedExhausted();
            }

            $this->notificationEvents->save($notificationEvent);
        });
    }
}
