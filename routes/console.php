<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('downloads:cleanup')->daily();
Schedule::command('quotas:reset')->monthlyOn(1, '00:05');
Schedule::command('subscriptions:process')->dailyAt('01:00');
Schedule::command('billing:expire-crypto-invoices')->hourly();

Schedule::command('queue:work --stop-when-empty --max-time=55 --tries=2')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/queue-cron.log'));
