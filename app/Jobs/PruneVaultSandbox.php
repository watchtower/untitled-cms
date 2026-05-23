<?php

namespace App\Jobs;

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

    public function handle(): void
    {
        $disk = Storage::disk('sandbox');
        $files = $disk->allFiles('');
        $count = 0;
        $now = time();

        foreach ($files as $file) {
            try {
                if ($now - $disk->lastModified($file) > 3600) { // 1 hour
                    $disk->delete($file);
                    $count++;
                }
            } catch (\Exception $e) {
                Log::warning("Failed to prune file {$file} from sandbox: " . $e->getMessage());
            }
        }

        if ($count > 0) {
            Log::info("Pruned {$count} files from vault sandbox.");
        }
    }
}
