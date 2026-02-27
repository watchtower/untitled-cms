<?php

namespace App\Services;

use App\Models\VaultAuditLog;
use App\Models\VaultFile;
use App\Models\VaultFolder;
use App\Jobs\OptimizeVaultImageJob;
use App\Vault\Pipes\DetectDoubleExtension;
use App\Vault\Pipes\GenerateUuid;
use App\Vault\Pipes\SandboxedScan;
use App\Vault\Pipes\SanitizeImage;
use App\Vault\Pipes\StoreMetadata;
use App\Vault\Pipes\ValidateMimeType;
use Illuminate\Http\UploadedFile;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VaultService
{
    public function upload(UploadedFile $file, ?string $folderId = null): VaultFile
    {
        // Define the pipeline
        $pipes = [
            DetectDoubleExtension::class,
            ValidateMimeType::class,
                // SandboxedScan::class, // Optional ClamAV
            SanitizeImage::class,
            GenerateUuid::class,
            StoreMetadata::class,
        ];

        // Ensure folder exists if provided
        if ($folderId) {
            $folder = VaultFolder::find($folderId);
            if (!$folder) {
                throw new \InvalidArgumentException('Folder not found.');
            }
        }

        $payload = [
            'file' => $file,
            'folder_id' => $folderId,
        ];

        // Enable SandboxedScan if configured
        if (config('vault.clamav_enabled')) {
            array_splice($pipes, 2, 0, SandboxedScan::class);
        }

        return app(Pipeline::class)
            ->send($payload)
            ->through($pipes)
            ->then(function ($payload) {
                $file = $payload['created_file'];
                $this->audit('file.upload', $file, null, $file->toArray());

                // Dispatch async WebP conversion job for supported images
                OptimizeVaultImageJob::dispatch($file);

                return $file;
            });
    }

    public function createFolder(string $name, ?string $parentId, $ownerId): VaultFolder
    {
        $folder = VaultFolder::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => $name,
            'parent_id' => $parentId,
            'owner_id' => $ownerId,
            // path_slug logic could be added here if needed for materialized path
            'path_slug' => $this->generatePathSlug($name, $parentId),
        ]);

        $this->audit('folder.create', $folder, null, $folder->toArray());

        return $folder;
    }

    public function renameFile(VaultFile $file, string $newName): void
    {
        $oldValues = ['original_name' => $file->original_name];

        // Sanitize new name (prevent traversal, specific chars)
        $cleanName = basename($newName);

        $file->update(['original_name' => $cleanName]);

        $this->audit('file.rename', $file, $oldValues, ['original_name' => $cleanName]);
    }

    public function moveFile(VaultFile $file, ?string $targetFolderId): void
    {
        $oldValues = ['folder_id' => $file->folder_id];

        if ($targetFolderId) {
            $folder = VaultFolder::findOrFail($targetFolderId);
        }

        $file->update(['folder_id' => $targetFolderId]);

        $this->audit('file.move', $file, $oldValues, ['folder_id' => $targetFolderId]);
    }

    public function deleteFile(VaultFile $file): void
    {
        $fileData = $file->toArray();
        $diskName = $file->is_public ? 'public' : 'vault';

        // Secure the file by appending .trashed so it's no longer publicly accessible
        if (Storage::disk($diskName)->exists($file->storage_path)) {
            Storage::disk($diskName)->move($file->storage_path, $file->storage_path . '.trashed');
        }

        $file->delete(); // Soft delete
        $this->audit('file.delete', $file, $fileData, null);
    }

    public function restoreFile(VaultFile $file): void
    {
        $diskName = $file->is_public ? 'public' : 'vault';

        // Revert the .trashed extension
        if (Storage::disk($diskName)->exists($file->storage_path . '.trashed')) {
            Storage::disk($diskName)->move($file->storage_path . '.trashed', $file->storage_path);
        }

        $file->restore();
        $this->audit('file.restore', $file, null, $file->toArray());
    }

    public function purgeFile(VaultFile $file): void
    {
        $fileData = $file->toArray();
        $diskName = $file->is_public ? 'public' : 'vault';

        // Remove the physical file (it might have .trashed appended if it was soft-deleted)
        if (Storage::disk($diskName)->exists($file->storage_path . '.trashed')) {
            Storage::disk($diskName)->delete($file->storage_path . '.trashed');
        } elseif (Storage::disk($diskName)->exists($file->storage_path)) {
            // Fallback just in case deleting an item that hasn't been soft-deleted first
            Storage::disk($diskName)->delete($file->storage_path);
        }

        $file->forceDelete(); // Hard delete
        $this->audit('file.purge', $file, $fileData, null);
    }

    public function renameFolder(VaultFolder $folder, string $newName): void
    {
        $oldValues = ['name' => $folder->name];
        $folder->update(['name' => $newName]);
        // Note: path_slug regeneration for children would be needed for a full implementation

        $this->audit('folder.rename', $folder, $oldValues, ['name' => $newName]);
    }

    public function deleteFolder(VaultFolder $folder): void
    {
        $folder->delete(); // Soft delete
        $this->audit('folder.delete', $folder, $folder->toArray(), null);
    }

    public function restoreFolder(VaultFolder $folder): void
    {
        $folder->restore();
        $this->audit('folder.restore', $folder, null, $folder->toArray());
    }

    public function purgeFolder(VaultFolder $folder): void
    {
        // Actual deletion of folder and cascading could be complex.
        // For MVP, we just forceDelete the folder record.
        $folderData = $folder->toArray();
        $folder->forceDelete();
        $this->audit('folder.purge', $folder, $folderData, null);
    }

    private function audit($event, $resource, $old = null, $new = null)
    {
        try {
            VaultAuditLog::create([
                'user_id' => Auth::id() ?? 'system',
                'event' => $event,
                'resource_type' => class_basename($resource),
                'resource_id' => $resource->id,
                'old_values' => $old,
                'new_values' => $new,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to write audit log: ' . $e->getMessage());
        }
    }

    private function generatePathSlug($name, $parentId)
    {
        $slug = \Illuminate\Support\Str::slug($name);
        if ($parentId) {
            $parent = VaultFolder::find($parentId);

            return $parent ? $parent->path_slug . '/' . $slug : '/' . $slug;
        }

        return '/' . $slug;
    }
}
