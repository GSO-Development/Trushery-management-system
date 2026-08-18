<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Recurring Overdue Facility Alert Escalations (Every 4 Hours)
 * Dispatches urgent escalation emails to authorized users for all RED / Overdue
 * Fixed Deposits, Working Capital loans, and Long Term Loans until resolved.
 */
Schedule::command('treasury:dispatch-overdue-alerts --cooldown=4')
    ->everyFourHours()
    ->name('treasury-4hr-overdue-escalation')
    ->withoutOverlapping()
    ->runInBackground();