<?php

use App\Jobs\PruneVaultSandbox;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('vault:purge --days=30')->daily();
Schedule::job(new PruneVaultSandbox)->hourly();
Schedule::command('vault:generate-alt-text')->weekly();
