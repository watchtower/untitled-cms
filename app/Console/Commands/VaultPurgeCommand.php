<?php

namespace App\Console\Commands;

use App\Models\VaultFile;
use App\Services\VaultService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class VaultPurgeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vault:purge {--days=30 : Number of days to keep deleted files in trash}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently delete files from the vault that have been soft-deleted older than N days';

    /**
     * Execute the console command.
     */
    public function handle(VaultService $vaultService)
    {
        $days = (int) $this->option('days');
        $date = Carbon::now()->subDays($days);

        $this->info("Fetching deleted vault files older than {$days} days ({$date->toDateTimeString()})...");

        // Find soft-deleted files older than N days
        $files = VaultFile::onlyTrashed()->where('deleted_at', '<=', $date)->get();

        if ($files->isEmpty()) {
            $this->info('No files found to purge.');

            return;
        }

        $count = $files->count();
        $this->withProgressBar($files, function ($file) use ($vaultService) {
            try {
                $vaultService->purgeFile($file);
            } catch (\Exception $e) {
                $this->error("Failed to purge file {$file->uuid}: ".$e->getMessage());
            }
        });

        $this->newLine();
        $this->info("Successfully purged {$count} files.");
    }
}
