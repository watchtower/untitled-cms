<?php

namespace App\Console\Commands;

use App\Models\VaultFile;
use App\Services\VaultService;
use Illuminate\Console\Command;

class VaultPurge extends Command
{
    /**
     * @var string
     */
    protected $signature = 'vault:purge {--days=30}';

    /**
     * @var string
     */
    protected $description = 'Permanently delete soft-deleted vault files older than X days';

    public function handle(VaultService $vaultService)
    {
        $days = (int) $this->option('days');
        $query = VaultFile::onlyTrashed()
            ->where('deleted_at', '<', now()->subDays($days));

        $count = $query->count();

        if ($count === 0) {
            $this->info('No files to purge.');

            return 0;
        }

        $this->info("Purging {$count} files...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $query->chunk(100, function ($files) use ($vaultService, $bar) {
            foreach ($files as $file) {
                $vaultService->purgeFile($file);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('Done.');

        return 0;
    }
}
