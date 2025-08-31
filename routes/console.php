<?php

use App\Console\Commands\ResetMonthlyCredits;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduler for resetting monthly credits
Schedule::command(ResetMonthlyCredits::class)
    ->monthly()
    ->at('00:00')
    ->description('Reset monthly credits for all users based on their subscription plan.')
    ->withoutOverlapping()
    ->runInBackground();

// Scheduler for cleaning up site monitor data based on retention periods
Schedule::command('monitors:cleanup')
    ->daily()
    ->at('02:00')
    ->description('Clean up old site monitor data based on user subscription retention periods.')
    ->withoutOverlapping();