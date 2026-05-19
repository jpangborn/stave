<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('stave:send-digests --frequency=daily')->dailyAt('07:00');
Schedule::command('stave:send-digests --frequency=weekly')->weeklyOn(1, '07:00');
