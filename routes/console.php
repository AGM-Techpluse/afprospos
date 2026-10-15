<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run scheduled commands in-process rather than via Schedule::command(): the production
// host disables proc_open (Symfony Process can't fork a subprocess) and has no ext-dom
// (Termwind can't render the scheduler's per-command summary for subprocess-backed events)
// — Schedule::call() sidesteps both, since it never forks and the scheduler skips that
// summary render for closure-based events.

// Checkout reservation window is 15 minutes (config('afprospos.checkout_reservation_minutes'));
// run every minute so a released reservation becomes available again promptly.
Schedule::call(fn () => Artisan::call('afprospos:sales:expire-checkouts'))
    ->name('afprospos:sales:expire-checkouts')
    ->everyMinute()
    ->withoutOverlapping();

// Repair authorization deadlines are hours-scale (config('afprospos.repairs.authorization_response_hours')),
// not minutes-scale like checkout — a coarser cadence is sufficient.
Schedule::call(fn () => Artisan::call('afprospos:repairs:expire-authorizations'))
    ->name('afprospos:repairs:expire-authorizations')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

// Collection deadlines/storage fees are a flat daily-rate model — one run per day is enough.
Schedule::call(fn () => Artisan::call('afprospos:collection:process-deadlines'))
    ->name('afprospos:collection:process-deadlines')
    ->daily()
    ->withoutOverlapping();

// Safety net only — DispatchNotificationJob's own backoff() is the primary retry path
// (NOTIF-BR-06); this catches anything lost at the queue level (worker restart, etc).
Schedule::call(fn () => Artisan::call('afprospos:notifications:retry-failed'))
    ->name('afprospos:notifications:retry-failed')
    ->everyTenMinutes()
    ->withoutOverlapping();
