<?php

namespace App\Jobs;

use App\Models\VaultFile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class OptimizeVaultImageJob implements ShouldQueue
{
    use Queueable;

    protected VaultFile $vaultFile;

    /**
     * Create a new job instance.
     */
    public function __construct(VaultFile $vaultFile)
    {
        $this->vaultFile = $vaultFile;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Check if the file still exists and is not already optimized
        if (!$this->vaultFile || $this->vaultFile->is_optimized) {
            return;
        }

        // Only process images (jpg, jpeg, png)
        $supportedExtensions = ['jpg', 'jpeg', 'png'];
        $extension = strtolower($this->vaultFile->extension);
        if (!in_array($extension, $supportedExtensions)) {
            return;
        }

        $diskName = $this->vaultFile->is_public ? 'public' : 'vault';
        $fullPath = Storage::disk($diskName)->path($this->vaultFile->storage_path);

        if (!file_exists($fullPath)) {
            Log::warning("OptimizeVaultImageJob: File not found at {$fullPath} for UUID {$this->vaultFile->uuid}");
            return;
        }

        try {
            // we will use the GD driver
            $manager = new ImageManager(new Driver());

            // read image from file system
            $image = $manager->read($fullPath);

            // Create a path for the optimized file
            $pathInfo = pathinfo($this->vaultFile->storage_path);
            $optimizedPath = $pathInfo['dirname'] . '/optimized_' . $pathInfo['filename'] . '.webp';

            // Encode to webp format with 85% quality
            $encoded = $image->toWebp(85);

            // Save the optimized file
            Storage::disk($diskName)->put($optimizedPath, (string) $encoded);

            // Update the VaultFile record
            $this->vaultFile->update([
                'optimized_path' => $optimizedPath,
                'optimized_size' => Storage::disk($diskName)->size($optimizedPath),
                'is_optimized' => true,
                'use_original' => false,
            ]);

            Log::info("Successfully optimized VaultFile {$this->vaultFile->uuid} to WebP.");

        } catch (\Exception $e) {
            Log::error("Failed to optimize VaultFile {$this->vaultFile->uuid}: " . $e->getMessage());
        }
    }
}
