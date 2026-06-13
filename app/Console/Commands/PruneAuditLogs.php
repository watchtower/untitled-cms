<?php

namespace App\Console\Commands;

use App\Models\VaultAuditLog;
use Illuminate\Console\Command;

class PruneAuditLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:prune-logs {--days= : Days to retain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete audit logs older than the configured TTL';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Default to retaining 1 year of audit logs if not specified
        $days = $this->option('days') ?? config('services.audit_log_ttl_days', 365);
        $cutoff = now()->subDays($days);

        $count = VaultAuditLog::where('created_at', '<', $cutoff)->delete();

        $this->info("Pruned {$count} audit logs older than {$days} days.");

        return Command::SUCCESS;
    }
}
