<?php

declare(strict_types=1);

namespace Domain\Notifications\Application\Commands;

/** Built by a Listener from another module's already-public Domain Event — this Command's write is what makes an event durable (the outbox write) instead of a fire-and-forget in-process dispatch. */
final readonly class RequestNotificationCommand
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public string $eventType,
        public string $sourceModule,
        public int $sourceId,
        public string $recipientType,
        public int $recipientId,
        public string $category,
        public array $payload,
    ) {}
}
