<?php

use App\Console\Commands\RefreshPerformanceHubRollupsCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(RefreshPerformanceHubRollupsCommand::class)
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command(RefreshPerformanceHubRollupsCommand::class)
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->runInBackground();
