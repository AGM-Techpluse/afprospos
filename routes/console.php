<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Checkout reservation window is 15 minutes (config('afprospos.checkout_reservation_minutes'));
// run every minute so a released reservation becomes available again promptly.
Schedule::command('afprospos:sales:expire-checkouts')
    ->everyMinute()
    ->withoutOverlapping();

// Repair authorization deadlines are hours-scale (config('afprospos.repairs.authorization_response_hours')),
// not minutes-scale like checkout — a coarser cadence is sufficient.
Schedule::command('afprospos:repairs:expire-authorizations')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

// Collection deadlines/storage fees are a flat daily-rate model — one run per day is enough.
Schedule::command('afprospos:collection:process-deadlines')
    ->daily()
    ->withoutOverlapping();
