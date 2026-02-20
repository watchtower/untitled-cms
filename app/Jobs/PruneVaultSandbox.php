<?php

namespace App\Jobs;

use App\Models\VaultFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PruneVaultSandbox implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Prune stale files in sandbox (older than 24h)
        $disk = Storage::disk('sandbox');
        $files = $disk->files(); // Retrieve all files in root of sandbox
        $now = now();

        foreach ($files as $file) {
            $lastModified = $disk->lastModified($file);
            if ($now->diffInHours(\Carbon\Carbon::createFromTimestamp($lastModified)) > 24) {
                $disk->delete($file);
                Log::info("Pruned stale sandbox file: {$file}");
            }
        }

        // 2. Prune Soft Deleted VaultFiles (older than 30 days)
        $staleRecords = VaultFile::onlyTrashed()
            ->where('deleted_at', '<', $now->subDays(30))
            ->get();

        foreach ($staleRecords as $record) {
            // Delete physical file from vault disk
            if ($record->storage_path && Storage::disk('vault')->exists($record->storage_path)) {
                Storage::disk('vault')->delete($record->storage_path);
            }

            // Force delete record
            $record->forceDelete();
            Log::info("Pruned permanently deleted file record: {$record->uuid}");
        }
    }
}
