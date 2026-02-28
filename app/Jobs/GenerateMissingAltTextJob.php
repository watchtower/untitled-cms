<?php

namespace App\Jobs;

use App\Models\VaultFile;
use App\Services\AiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class GenerateMissingAltTextJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(AiService $aiService)
    {
        $files = VaultFile::whereNull('alt_text')
            ->where('mime_type', 'like', 'image/%')
            ->cursor();

        foreach ($files as $file) {
            /** @var VaultFile $file */
            try {
                $diskName = $file->is_public ? 'public' : 'vault';
                $binary = Storage::disk($diskName)->get($file->storage_path);

                if (!$binary)
                    continue;

                $base64 = base64_encode($binary);
                $dataUri = 'data:' . $file->mime_type . ';base64,' . $base64;

                $altText = $aiService->generateAltTextFromBase64($dataUri, $file->mime_type);
                $file->update(['alt_text' => $altText]);
            } catch (\Exception $e) {
                Log::error("Job failed to generate alt text for VaultFile {$file->uuid}: " . $e->getMessage());
            }
        }
    }
}
