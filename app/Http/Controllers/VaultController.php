<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveAiImageRequest;
use App\Jobs\GenerateMissingAltTextJob;
use App\Models\VaultFile;
use App\Models\VaultFolder;
use App\Services\VaultService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class VaultController extends Controller
{
    protected $vaultService;

    public function __construct(VaultService $vaultService)
    {
        $this->vaultService = $vaultService;
    }

    public function adminPage()
    {
        $this->authorizeGlobalMediaView();

        return Inertia::render('Vault/Index', [
            'maxUploadSize' => min(
                $this->parsePhpIniSize(ini_get('upload_max_filesize')),
                $this->parsePhpIniSize(ini_get('post_max_size'))
            ),
        ]);
    }

    /**
     * Convert PHP ini size strings (e.g. "50M", "8G", "512K") to bytes.
     * A plain (int) cast silently truncates the unit suffix and returns the wrong value.
     */
    private function parsePhpIniSize(string $size): int
    {
        $value = (int) $size;
        $unit = strtolower(substr(trim($size), -1));

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    public function list(Request $request)
    {
        $this->authorizeGlobalMediaView();

        $files = $this->buildListQuery($request)->paginate(50);

        return response()->json($files);
    }

    public function upload(Request $request)
    {
        $this->authorizeGlobalMediaCreate();

        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file', // Max size checked in VaultService/php.ini/config
            'folder_id' => 'nullable|string|exists:'.VaultFolder::class.',_id',
        ]);

        $uploadedFiles = [];
        $errors = [];

        $targetFolder = null;
        if ($request->folder_id) {
            $targetFolder = VaultFolder::findOrFail($request->folder_id);
            if (Gate::denies('update', $targetFolder)) {
                abort(403, 'Permission denied for folder');
            }
        }

        $isPublic = $request->boolean('is_public', true);

        foreach ($request->file('files') as $file) {
            try {
                $uploadedFiles[] = $this->vaultService->upload($file, $request->folder_id, $targetFolder, $isPublic);
            } catch (\Exception $e) {
                $errors[] = [
                    'filename' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'uploaded' => $uploadedFiles,
            'errors' => $errors,
        ]);
    }

    public function saveAiImage(SaveAiImageRequest $request)
    {
        try {
            $uploadedFile = $request->getPreparedUploadedFile();

            $vaultFile = $this->vaultService->upload($uploadedFile, $request->input('folder_id'));

            @unlink($uploadedFile->getPathname());

            return response()->json(['file' => $vaultFile], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function serve(string $uuid)
    {
        $file = VaultFile::withTrashed()->where('uuid', $uuid)->firstOrFail();

        // Check permission
        if (Gate::denies('view', $file)) {
            abort(403);
        }

        $diskName = $file->is_public ? 'public' : 'vault';
        $storagePath = $file->storage_path;

        if ($file->trashed()) {
            $storagePath .= '.trashed';
        }

        // Check if we should serve the optimized file
        $isOptimizedRequest = $file->is_optimized && ! $file->use_original && $file->optimized_path;

        if ($isOptimizedRequest) {
            // The optimized file is never renamed on delete — only storage_path gets .trashed
            $storagePath = $file->optimized_path;
        }

        $path = Storage::disk($diskName)->path($storagePath);

        $etag = md5($file->updated_at.$file->size_bytes);
        $lastModified = $file->updated_at->toRfc7231String();

        if (request()->header('If-None-Match') === "\"{$etag}\"") {
            return response('', 304);
        }

        $contentType = ($file->is_optimized && ! $file->use_original && str_ends_with($storagePath, '.webp'))
            ? 'image/webp'
            : $file->mime_type;

        $filename = $file->original_name;

        return response()->file($path, [
            'ETag' => "\"{$etag}\"",
            'Last-Modified' => $lastModified,
            'Cache-Control' => $file->is_public ? 'public, max-age=31536000' : 'private, no-store',
            'Content-Type' => $contentType,
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function servePublic(string $uuid, string $extension = '')
    {
        // $extension is the segment after the dot in /media/{uuid}.{extension}.
        // It is intentionally ignored — the file is resolved by UUID alone.
        // Its only purpose is to give browsers and CDNs a proper file extension in the URL.
        $file = VaultFile::where('uuid', $uuid)->where('is_public', true)->firstOrFail();

        $diskName = 'public';
        $storagePath = $file->resolveServingPath();

        $path = Storage::disk($diskName)->path($storagePath);

        if (! file_exists($path)) {
            abort(404);
        }

        $etag = md5($file->updated_at.$file->size_bytes);
        $lastModified = $file->updated_at->toRfc7231String();

        // Fix #6: compare If-Modified-Since as a parsed HTTP date rather than a string.
        // CDNs may normalise the date format slightly (e.g. extra whitespace, different
        // day-name capitalisation), so string equality produces false mismatches.
        $ifModifiedSince = request()->header('If-Modified-Since');
        $notModified = request()->header('If-None-Match') === "\"{$etag}\""
            || ($ifModifiedSince && $this->parseHttpDate($ifModifiedSince) >= $file->updated_at->timestamp);

        if ($notModified) {
            return response('', 304);
        }

        $contentType = ($file->is_optimized && str_ends_with($storagePath, '.webp')) ? 'image/webp' : $file->mime_type;

        return response()->file($path, [
            'ETag' => "\"{$etag}\"",
            'Last-Modified' => $lastModified,
            'Cache-Control' => 'public, max-age=31536000',
            'Content-Type' => $contentType,
        ]);
    }

    public function checkDuplicate(Request $request)
    {
        $this->authorizeGlobalMediaView();

        $request->validate(['hash' => 'required|string']);

        $file = VaultFile::where('hash_sha256', $request->hash)
            ->whereNull('deleted_at')
            ->with('folder')
            ->first();

        return response()->json([
            'isDuplicate' => (bool) $file,
            'file' => $file ? $file->only(['uuid', 'original_name', 'created_at', 'folder']) : null,
        ]);
    }

    public function batchRestore(Request $request)
    {
        $request->validate([
            'uuids' => 'required|array',
            'uuids.*' => 'string|exists:vault_files,uuid',
        ]);

        $files = VaultFile::onlyTrashed()->whereIn('uuid', $request->uuids)->get();
        $restoredCount = 0;

        foreach ($files as $file) {
            if (Gate::allows('delete', $file)) {
                $this->vaultService->restoreFile($file);
                $restoredCount++;
            }
        }

        return response()->json([
            'message' => "Successfully restored {$restoredCount} item(s).",
            'restored_count' => $restoredCount,
        ]);
    }

    public function emptyTrash(Request $request)
    {
        $user = auth()->user();

        $query = VaultFile::onlyTrashed();

        // Unless they have global delete permission, only empty THEIR trash
        if (! $user->hasPermission('media.delete')) {
            $query->where('uploaded_by', $user->id);
        }

        $deletedCount = 0;

        $query->chunk(100, function ($files) use (&$deletedCount) {
            foreach ($files as $file) {
                $this->vaultService->purgeFile($file);
                $deletedCount++;
            }
        });

        return response()->json([
            'message' => "Trash emptied. {$deletedCount} items permanently deleted.",
            'deleted_count' => $deletedCount,
        ]);
    }

    public function rename(Request $request, string $uuid)
    {
        $file = VaultFile::where('uuid', $uuid)->firstOrFail();

        if (Gate::denies('update', $file)) {
            abort(403);
        }

        $request->validate(['name' => 'required|string|max:255']);

        $this->vaultService->renameFile($file, $request->name);

        return response()->json(['message' => 'Renamed successfully', 'file' => $file]);
    }

    public function updateAltText(Request $request, string $uuid)
    {
        $file = VaultFile::where('uuid', $uuid)->firstOrFail();

        if (Gate::denies('update', $file)) {
            abort(403);
        }

        $request->validate(['alt_text' => 'nullable|string|max:500']);

        $file->update(['alt_text' => $request->input('alt_text')]);

        return response()->json(['message' => 'Alt text updated', 'alt_text' => $file->alt_text]);
    }

    public function toggleOptimization(Request $request, string $uuid)
    {
        $file = VaultFile::where('uuid', $uuid)->firstOrFail();

        if (Gate::denies('update', $file)) {
            abort(403);
        }

        $request->validate(['use_original' => 'required|boolean']);

        $file->update(['use_original' => $request->boolean('use_original')]);

        return response()->json([
            'message' => 'Optimization preference updated',
            'use_original' => $file->use_original,
            'url' => $file->url, // Return the new URL so the frontend can update instantly
        ]);
    }

    public function batchMove(Request $request)
    {
        $request->validate([
            'uuids' => 'required|array',
            'uuids.*' => 'string|exists:vault_files,uuid',
            'folder_id' => 'nullable|string|exists:vault_folders,_id',
        ]);

        $uuids = $request->input('uuids');
        $folderId = $request->input('folder_id');

        // Check target folder permission
        if ($folderId) {
            $targetFolder = VaultFolder::findOrFail($folderId);
            if (Gate::denies('update', $targetFolder)) {
                abort(403, 'Permission denied for target folder.');
            }
        }

        $files = VaultFile::whereIn('uuid', $uuids)->get();
        $movedCount = 0;

        foreach ($files as $file) {
            /** @var VaultFile $file */
            if (Gate::allows('update', $file)) {
                $this->vaultService->moveFile($file, $folderId);
                $movedCount++;
            }
        }

        return response()->json([
            'message' => "Successfully moved {$movedCount} item(s).",
            'moved_count' => $movedCount,
        ]);
    }

    public function batchDelete(Request $request)
    {
        $request->validate([
            'uuids' => 'required|array',
            'uuids.*' => 'string|exists:vault_files,uuid',
        ]);

        $uuids = $request->input('uuids');
        $files = VaultFile::whereIn('uuid', $uuids)->get();
        $deletedCount = 0;

        foreach ($files as $file) {
            /** @var VaultFile $file */
            if (Gate::allows('delete', $file)) {
                $this->vaultService->deleteFile($file);
                $deletedCount++;
            }
        }

        return response()->json([
            'message' => "Successfully deleted {$deletedCount} item(s).",
            'deleted_count' => $deletedCount,
        ]);
    }

    public function generateMissingAltText(Request $request)
    {
        $this->authorize('update', VaultFile::class);

        GenerateMissingAltTextJob::dispatch();

        return response()->json([
            'message' => 'Alt-text generation job dispatched successfully. Images will be updated in the background.',
        ]);
    }

    public function move(Request $request, string $uuid)
    {
        $file = VaultFile::where('uuid', $uuid)->firstOrFail();

        if (Gate::denies('update', $file)) {
            abort(403);
        }

        $request->validate(['folder_id' => 'nullable|string|exists:'.VaultFolder::class.',_id']);

        // Check write permission on target folder
        if ($request->folder_id) {
            $targetFolder = VaultFolder::findOrFail($request->folder_id);
            if (Gate::denies('update', $targetFolder)) {
                abort(403, 'Cannot move to target folder');
            }
        }

        $this->vaultService->moveFile($file, $request->folder_id);

        return response()->json(['message' => 'Moved successfully', 'file' => $file]);
    }

    public function destroy(string $uuid)
    {
        $file = VaultFile::where('uuid', $uuid)->firstOrFail();

        if (Gate::denies('delete', $file)) {
            abort(403);
        }

        $this->vaultService->deleteFile($file);

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function trash(Request $request)
    {
        $this->authorizeGlobalMediaView();

        $files = VaultFile::onlyTrashed()->orderBy('deleted_at', 'desc')->paginate(50);

        return response()->json($files);
    }

    public function restore(string $uuid)
    {
        $file = VaultFile::onlyTrashed()->where('uuid', $uuid)->firstOrFail();

        if (Gate::denies('delete', $file)) { // Or create a specific 'restore' gate if needed
            abort(403);
        }

        $this->vaultService->restoreFile($file);

        return response()->json(['message' => 'Restored successfully']);
    }

    public function forceDestroy(string $uuid)
    {
        $file = VaultFile::onlyTrashed()->where('uuid', $uuid)->firstOrFail();

        if (Gate::denies('delete', $file)) { // Or create a specific 'forceDelete' gate
            abort(403);
        }

        $this->vaultService->purgeFile($file);

        return response()->json(['message' => 'Permanently deleted successfully']);
    }

    private function authorizeGlobalMediaView(): void
    {
        if (! auth()->user()->hasPermission('media.view')) {
            abort(403, 'Unauthorized');
        }
    }

    private function authorizeGlobalMediaCreate(): void
    {
        if (! auth()->user()->hasPermission('media.create')) {
            abort(403, 'Unauthorized');
        }
    }

    private function buildListQuery(Request $request): Builder
    {
        $query = VaultFile::with('folder');

        $folderId = $request->query('folder_id');
        if ($folderId) {
            $query->where('folder_id', $folderId);
        } else {
            $query->whereNull('folder_id');
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('original_name', 'like', "%{$search}%")
                    ->orWhere('alt_text', 'like', "%{$search}%");
            });
        }

        $type = $request->query('type');
        if ($type === 'image') {
            $query->where('mime_type', 'like', 'image/%');
        } elseif ($type === 'document') {
            $query->whereNot('mime_type', 'like', 'image/%');
        }

        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Parse an HTTP-date header value (RFC 7231 / RFC 850 / asctime) into a Unix timestamp.
     * Returns 0 if the value cannot be parsed, which causes the 304 condition to be false
     * (safe fallback — serves the full response rather than a wrong 304).
     */
    private function parseHttpDate(string $value): int
    {
        $ts = strtotime($value);

        return $ts !== false ? $ts : 0;
    }
}
