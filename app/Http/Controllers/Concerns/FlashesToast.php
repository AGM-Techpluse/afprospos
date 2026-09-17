<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

/**
 * Bridges a controller's post-redirect outcome to the toast queue
 * (Components/Feedback/Toast.tsx) via one session-flash key, `flash`,
 * shared by HandleInertiaRequests and consumed by useFlashToast on the
 * next page load — the standard Inertia flash pattern (UI/UX §17).
 */
trait FlashesToast
{
    protected function flashSuccess(string $title, ?string $message = null): void
    {
        $this->flash('success', $title, $message);
    }

    protected function flashError(string $title, ?string $message = null): void
    {
        $this->flash('danger', $title, $message);
    }

    protected function flashWarning(string $title, ?string $message = null): void
    {
        $this->flash('warning', $title, $message);
    }

    protected function flashInfo(string $title, ?string $message = null): void
    {
        $this->flash('info', $title, $message);
    }

    private function flash(string $kind, string $title, ?string $message): void
    {
        session()->flash('flash', ['kind' => $kind, 'title' => $title, 'message' => $message]);
    }
}
