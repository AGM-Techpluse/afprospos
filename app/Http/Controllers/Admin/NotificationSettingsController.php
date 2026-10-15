<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Domain\Notifications\Application\Queries\RecentFailedNotificationsQuery;
use Domain\Notifications\Infrastructure\Channels\Email\Smtp\SmtpMailer;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only diagnostics only — no POST routes, no forms. Provider
 * credentials stay in .env per ADD §21A; this page never displays or
 * accepts a secret, only whether one is configured.
 */
final class NotificationSettingsController
{
    public function __construct(
        private readonly RecentFailedNotificationsQuery $recentFailed,
        private readonly SmtpMailer $smtp,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Settings/Notifications/Index', [
            'resendConfigured' => filled(config('services.resend.key')),
            'fallbackConfigured' => $this->smtp->isConfigured(),
            'defaultMailer' => config('mail.default'),
            'mandatoryCategories' => config('afprospos.notifications.mandatory_categories', []),
            'maxDeliveryAttempts' => config('afprospos.notifications.max_delivery_attempts'),
            'recentFailed' => $this->recentFailed->recent(),
        ]);
    }
}
