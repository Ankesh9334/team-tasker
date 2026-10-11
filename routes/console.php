<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

try {
    Schedule::command('app:auto-stop-timers')->dailyAt('20:00');
} catch (\Exception $e) {
    // Fallback if database is not migrated yet
    Schedule::command('app:auto-stop-timers')->dailyAt('20:00');
}
