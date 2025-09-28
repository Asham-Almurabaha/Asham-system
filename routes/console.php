<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if ($time = config('notifications.zakat.daily_at')) {
    Schedule::command('zakat:notify')->dailyAt($time)->withoutOverlapping();
}

if ($time = config('notifications.debts.daily_at')) {
    Schedule::command('debts:notify-due')->dailyAt($time)->withoutOverlapping();
}
