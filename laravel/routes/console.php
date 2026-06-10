<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
Schedule::command('billing:generate-monthly')->monthlyOn(1, '01:00');
Schedule::command('collections:flag-overdue')->dailyAt('02:00');
Schedule::command('collections:send-reminders')->dailyAt('03:00');
Schedule::command('sales:expire-reservations')->dailyAt('04:00');
// Retired: payment plans use flexible collection, not fixed monthly instalment invoices.
// Schedule::command('sales:post-installment-invoices')->dailyAt('05:00');
// Runs every hour
Schedule::command('billing:cleanup')->hourly();