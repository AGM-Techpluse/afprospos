<?php

declare(strict_types=1);

namespace Domain\Notifications\Domain\Entities;

use DateTimeImmutable;
use Domain\Notifications\Domain\Exceptions\InvalidNotificationEventTransition;
use Domain\Notifications\Domain\Policies\NotificationEventTransitionPolicy;
use Domain\Notifications\Domain\ValueObjects\NotificationEventId;

/** DBDD §20.1, plus `payload`/`read_at` — see the Phase 8 slice-1 plan's "deliberate schema additions" note for why (a snapshot of the triggering event's own facts, and real in-app read/unread state). This IS the outbox: business modules just dispatch Domain Events (already true); a Listener turns the ones it cares about into one of these. */
final class NotificationEvent
{
    private function __construct(
        private readonly ?NotificationEventId $id,
        private readonly string $eventType,
        private readonly string $sourceModule,
        private readonly int $sourceId,
        private readonly string $recipientType,
        private readonly int $recipientId,
        private readonly string $category,
        private readonly array $payload,
        private string $status,
        private ?DateTimeImmutable $readAt,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function queue(
        string $eventType,
        string $sourceModule,
        int $sourceId,
        string $recipientType,
        int $recipientId,
        string $category,
        array $payload,
    ): self {
        return new self(null, $eventType, $sourceModule, $sourceId, $recipientType, $recipientId, $category, $payload, 'queued', null);
    }

    public static function reconstitute(
        NotificationEventId $id,
        string $eventType,
        string $sourceModule,
        int $sourceId,
        string $recipientType,
        int $recipientId,
        string $category,
        array $payload,
        string $status,
        ?DateTimeImmutable $readAt,
    ): self {
        return new self($id, $eventType, $sourceModule, $sourceId, $recipientType, $recipientId, $category, $payload, $status, $readAt);
    }

    public function markDelivered(): void
    {
        $this->assertTransition('delivered');
        $this->status = 'delivered';
    }

    public function markFailedExhausted(): void
    {
        $this->assertTransition('failed_exhausted');
        $this->status = 'failed_exhausted';
    }

    public function markRead(DateTimeImmutable $now): void
    {
        $this->readAt = $now;
    }

    private function assertTransition(string $to): void
    {
        if (! (new NotificationEventTransitionPolicy)->canTransition($this->status, $to)) {
            throw InvalidNotificationEventTransition::forNotificationEvent($this->id?->value ?? 0, $this->status, $to);
        }
    }

    public function id(): ?NotificationEventId
    {
        return $this->id;
    }

    public function eventType(): string
    {
        return $this->eventType;
    }

    public function sourceModule(): string
    {
        return $this->sourceModule;
    }

    public function sourceId(): int
    {
        return $this->sourceId;
    }

    public function recipientType(): string
    {
        return $this->recipientType;
    }

    public function recipientId(): int
    {
        return $this->recipientId;
    }

    public function category(): string
    {
        return $this->category;
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return $this->payload;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function readAt(): ?DateTimeImmutable
    {
        return $this->readAt;
    }
}
