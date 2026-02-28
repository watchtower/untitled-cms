<?php

namespace App\Vault\Pipes;

use App\Models\VaultFile;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class StoreMetadata
{
    public function handle($payload, Closure $next)
    {
        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $payload['file'];
        $uuid = $payload['uuid'];
        $folderId = $payload['folder_id'] ?? null;

        $extension = $file->extension() ?: $file->getClientOriginalExtension();
        $storageFilename = $uuid . '.' . $extension;

        // Move from temp/sandbox to final public vault disk since it passed the pipeline
        $path = $file->storeAs('vault', $storageFilename, 'public');

        if (!$path) {
            throw new \Exception('Failed to store file on public disk.');
        }

        // Calculate hash
        $hash = hash_file('sha256', Storage::disk('public')->path($path));

        // Create Database Record
        $vaultFile = VaultFile::create([
            'uuid' => $uuid,
            'folder_id' => $folderId,
            'storage_path' => $path,
            'original_name' => strip_tags($file->getClientOriginalName()),
            'mime_type' => $file->getMimeType(),
            'extension' => $extension,
            'size_bytes' => $file->getSize(),
            'hash_sha256' => $hash,
            'uploaded_by' => Auth::id(),
            'is_public' => true,
            'validation_status' => $payload['validation_status'] ?? 'safe',
            'moderation_reason' => $payload['moderation_reason'] ?? null,
            'alt_text' => strip_tags($file->getClientOriginalName()), // Default
        ]);

        // If it's an image, get dimensions
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $fullPath = Storage::disk('public')->path($path);
            try {
                [$width, $height] = getimagesize($fullPath);
                $vaultFile->update([
                    'width' => $width,
                    'height' => $height,
                ]);
            } catch (\Exception $e) {
                // Ignore if getimagesize fails
            }
        }

        $payload['created_file'] = $vaultFile;

        return $next($payload);
    }
}
