<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule; 

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('task:auto-assign')->everyMinute();

// 🔥 TIMEZONE FIX: Explicitly 'Asia/Kolkata' declare karna zaroori hai
Schedule::command('task:daily-alert')->timezone('Asia/Kolkata')->dailyAt('08:00');
Schedule::command('task:evening-summary')->timezone('Asia/Kolkata')->dailyAt('18:15');
Schedule::command('greetings:send-daily')->timezone('Asia/Kolkata')->dailyAt('00:00');