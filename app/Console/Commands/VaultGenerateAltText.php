<?php

namespace App\Console\Commands;

use App\Models\VaultFile;
use App\Services\AiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class VaultGenerateAltText extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vault:generate-alt-text {--limit=50 : Number of images to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Batch generate missing alt text for Vault images using AI vision';

    /**
     * Execute the console command.
     */
    public function handle(AiService $aiService)
    {
        $limit = (int) $this->option('limit');

        $files = VaultFile::whereNull('alt_text')
            ->where('mime_type', 'like', 'image/%')
            ->limit($limit)
            ->get();

        if ($files->isEmpty()) {
            $this->info('No images without alt text found.');
            return;
        }

        $this->info("Processing {$files->count()} images...");
        $bar = $this->output->createProgressBar($files->count());
        $bar->start();

        foreach ($files as $file) {
            /** @var VaultFile $file */
            try {
                $diskName = $file->is_public ? 'public' : 'vault';
                $binary = Storage::disk($diskName)->get($file->storage_path);

                if (!$binary) {
                    $bar->advance();
                    continue;
                }

                $base64 = base64_encode($binary);
                $dataUri = 'data:' . $file->mime_type . ';base64,' . $base64;

                $altText = $aiService->generateAltTextFromBase64($dataUri, $file->mime_type);

                $file->update(['alt_text' => $altText]);
            } catch (\Exception $e) {
                // Skip if fails, maybe log it
                \Illuminate\Support\Facades\Log::error("Failed to generate alt text for VaultFile {$file->uuid}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Success: Alt text generation complete.');
    }
}
