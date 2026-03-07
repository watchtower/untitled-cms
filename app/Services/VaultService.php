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
use App\Vault\Pipes\ModerationCheck;
use Illuminate\Http\UploadedFile;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VaultService
{
    public function upload(UploadedFile $file, ?string $folderId = null): VaultFile
    {
        $this->ensureFolderExists($folderId);

        $pipes = [
            DetectDoubleExtension::class,
            ValidateMimeType::class,
            SanitizeImage::class,
            ModerationCheck::class,
            GenerateUuid::class,
            StoreMetadata::class,
        ];

        if (config('vault.clamav_enabled')) {
            array_splice($pipes, 2, 0, SandboxedScan::class);
        }

        return app(Pipeline::class)
            ->send(new \App\Vault\DTOs\VaultPipelinePayload($file, $folderId))
            ->through($pipes)
            ->then(function (\App\Vault\DTOs\VaultPipelinePayload $payload) {
                $file = $payload->created_file;
                $this->audit('file.upload', $file, null, $file->toArray());
                OptimizeVaultImageJob::dispatch($file);

                return $file;
            });
    }

    private function ensureFolderExists(?string $folderId): void
    {
        if ($folderId && !VaultFolder::where('id', $folderId)->exists()) {
            throw new \InvalidArgumentException('Folder not found.');
        }
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

        // Secure the file by appending .trashed so it's no longer publicly accessible
        $this->moveStorageFile($file, '', '.trashed');

        $file->delete(); // Soft delete
        $this->audit('file.delete', $file, $fileData, null);
    }

    public function restoreFile(VaultFile $file): void
    {
        // Revert the .trashed extension
        $this->moveStorageFile($file, '.trashed', '');

        $file->restore();
        $this->audit('file.restore', $file, null, $file->toArray());
    }

    public function purgeFile(VaultFile $file): void
    {
        $fileData = $file->toArray();

        // Remove the physical file (it might have .trashed appended if we soft-deleted it)
        $this->deleteStorageFile($file, '.trashed');
        // Fallback for hard-deleting an active file directly
        $this->deleteStorageFile($file, '');

        $file->forceDelete(); // Hard delete
        $this->audit('file.purge', $file, $fileData, null);
    }

    private function resolveDiskPrefix(VaultFile $file): string
    {
        return $file->is_public ? 'public' : 'vault';
    }

    private function moveStorageFile(VaultFile $file, string $fromSuffix = '', string $toSuffix = ''): void
    {
        $disk = Storage::disk($this->resolveDiskPrefix($file));
        $fromPath = $file->storage_path . $fromSuffix;
        $toPath = $file->storage_path . $toSuffix;

        if ($disk->exists($fromPath)) {
            $disk->move($fromPath, $toPath);
        }
    }

    private function deleteStorageFile(VaultFile $file, string $suffix = ''): void
    {
        $disk = Storage::disk($this->resolveDiskPrefix($file));
        $path = $file->storage_path . $suffix;

        if ($disk->exists($path)) {
            $disk->delete($path);
        }
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
        $slug = '/' . \Illuminate\Support\Str::slug($name);

        if (!$parentId) {
            return $slug;
        }

        $parent = VaultFolder::find($parentId);
        return $parent ? $parent->path_slug . $slug : $slug;
    }
}
