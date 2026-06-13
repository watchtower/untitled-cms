<?php

namespace App\Console\Commands;

use App\Models\EmailLog;
use Illuminate\Console\Command;

class PruneEmailLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:prune-logs {--days= : Days to retain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete email logs older than the configured TTL';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->option('days') ?? config('services.email_log_ttl_days', 90);
        $cutoff = now()->subDays($days);

        $count = EmailLog::where('created_at', '<', $cutoff)->delete();

        $this->info("Pruned {$count} email logs older than {$days} days.");

        return Command::SUCCESS;
    }
}
