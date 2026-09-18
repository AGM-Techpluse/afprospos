<?php

declare(strict_types=1);

namespace Domain\Notifications\Application\Contracts;

use Domain\Notifications\Application\DTOs\DeliveryResult;
use Domain\Notifications\Application\DTOs\NotificationMessage;

/** ADD §21A: "business modules know only that an email channel exists" — NotificationEngine is itself the only caller, and it holds two of these (Resend, then SMTP) in priority order for NOTIF-BR-08 failover, rather than one bound implementation being swapped. */
interface EmailChannel
{
    public function send(NotificationMessage $message): DeliveryResult;

    /** Whether this channel has real provider configuration (e.g. RESEND_API_KEY) — lets NotificationEngine and the Admin diagnostics page skip/report a channel without attempting a send that's guaranteed to fail. */
    public function isConfigured(): bool;
}
