<?php

declare(strict_types=1);

namespace Domain\Notifications\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Notifications\Application\Commands\RequestNotificationCommand;
use Domain\Notifications\Domain\Entities\NotificationEvent;
use Domain\Notifications\Domain\Repositories\NotificationEventRepository;
use Domain\Notifications\Infrastructure\Jobs\DispatchNotificationJob;

/** NOTIF-BR-01/02: the outbox write. A Listener calls this after another module's Handler has already committed its own business transaction — this Handler's job is only to make the "notify" fact durable and hand delivery to the queue, never to attempt delivery itself. */
final class RequestNotificationHandler
{
    public function __construct(
        private readonly NotificationEventRepository $notificationEvents,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(RequestNotificationCommand $command): int
    {
        $id = $this->atomic->run(function () use ($command): int {
            $notificationEvent = NotificationEvent::queue(
                eventType: $command->eventType,
                sourceModule: $command->sourceModule,
                sourceId: $command->sourceId,
                recipientType: $command->recipientType,
                recipientId: $command->recipientId,
                category: $command->category,
                payload: $command->payload,
            );

            $id = $this->notificationEvents->save($notificationEvent);

            $this->audit->record(
                module: 'Notifications',
                eventType: 'NotificationRequested',
                actorStaffId: null,
                actorRoleSnapshot: null,
                subjectType: 'notification_event',
                subjectId: $id->value,
                beforeState: null,
                afterState: ['event_type' => $command->eventType, 'recipient_type' => $command->recipientType, 'recipient_id' => $command->recipientId],
            );

            return $id->value;
        });

        DispatchNotificationJob::dispatch($id);

        return $id;
    }
}
