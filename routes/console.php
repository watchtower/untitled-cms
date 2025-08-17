<?php

use App\Console\Commands\ProcessSubscriptionBenefits;
use App\Console\Commands\ResetResettableCounters;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// L33t Bytes Scheduler Configuration
Schedule::command(ResetResettableCounters::class, ['--type=daily'])
    ->daily()
    ->at('00:00')
    ->description('Reset daily Bits counters for all users')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command(ResetResettableCounters::class, ['--type=weekly'])
    ->weekly()
    ->sundays()
    ->at('00:00')
    ->description('Reset weekly Bits counters for all users')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command(ProcessSubscriptionBenefits::class)
    ->monthly()
    ->at('00:00')
    ->description('Process monthly L33t Bytes subscription benefits')
    ->withoutOverlapping()
    ->runInBackground();
