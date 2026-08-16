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

// 🔥 NAYA: Daily Telecalling & Late Report theek 18:16 par jayegi
Schedule::command('report:daily-calling')->timezone('Asia/Kolkata')->dailyAt('18:16');

Schedule::command('greetings:send-daily')->timezone('Asia/Kolkata')->dailyAt('00:00');