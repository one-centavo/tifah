<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:scan-expiration-alerts')
    ->dailyAt('00:00')
    ->description('Daily automated scan for inventory expiration alerts');

Schedule::command('app:send-weekly-expiration-report')
    ->weeklyOn(1, '07:00')
    ->description('Send weekly consolidated expiration report to administrators');
