<?php

namespace App\Vault\Pipes;

use App\Models\VaultFile;
use App\Vault\DTOs\VaultPipelinePayload;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class StoreMetadata
{
    public function handle(VaultPipelinePayload $payload, Closure $next): mixed
    {
        /** @var UploadedFile $file */
        $file = $payload->file;
        $uuid = $payload->uuid;

        $path = $this->storeToDisk($file, $uuid, $payload);
        $vaultFile = $this->createVaultFileRecord($file, $uuid, $path, $payload);
        $this->processImageDimensions($file, $path, $vaultFile);

        $payload->created_file = $vaultFile;

        return $next($payload);
    }

    private function storeToDisk(UploadedFile $file, string $uuid, VaultPipelinePayload $payload): string
    {
        $extension = $file->extension() ?: $file->getClientOriginalExtension();
        $storageFilename = $uuid.'.'.$extension;
        $disk = $payload->is_public ? 'public' : 'vault';

        $folderPath = '';
        if ($payload->folder) {
            $folderPath = '/'.ltrim($payload->folder->path_slug, '/');
        }

        $path = $file->storeAs('vault'.$folderPath, $storageFilename, $disk);

        if (! $path) {
            throw new \Exception("Failed to store file on $disk disk.");
        }

        return $path;
    }

    private function createVaultFileRecord(UploadedFile $file, string $uuid, string $path, VaultPipelinePayload $payload): VaultFile
    {
        $disk = $payload->is_public ? 'public' : 'vault';
        $hash = hash_file('sha256', Storage::disk($disk)->path($path));

        return VaultFile::create([
            'uuid' => $uuid,
            'folder_id' => $payload->folder_id ?? null,
            'storage_path' => $path,
            'original_name' => strip_tags($file->getClientOriginalName()),
            'mime_type' => $file->getMimeType(),
            'extension' => $file->extension() ?: $file->getClientOriginalExtension(),
            'size_bytes' => $file->getSize(),
            'hash_sha256' => $hash,
            'uploaded_by' => Auth::id(),
            'is_public' => $payload->is_public,
            'validation_status' => $payload->validation_status ?? 'safe',
            'moderation_reason' => $payload->moderation_reason ?? null,
            'alt_text' => strip_tags($file->getClientOriginalName()), // Default
        ]);
    }

    private function processImageDimensions(UploadedFile $file, string $path, VaultFile $vaultFile): void
    {
        if (! str_starts_with($file->getMimeType(), 'image/')) {
            return;
        }

        $disk = $vaultFile->is_public ? 'public' : 'vault';
        $fullPath = Storage::disk($disk)->path($path);

        try {
            if ($dimensions = @getimagesize($fullPath)) {
                $vaultFile->update([
                    'width' => $dimensions[0],
                    'height' => $dimensions[1],
                ]);
            }
        } catch (\Exception $e) {
            // Ignore if getimagesize fails
        }
    }
}
