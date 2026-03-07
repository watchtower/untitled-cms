<?php

namespace App\Http\Controllers;

use App\Models\VaultFile;
use App\Models\VaultFolder;
use App\Services\VaultService;
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
        // Check global permission
        if (!auth()->user()->hasRole('Super Admin') && !auth()->user()->hasPermission('media.view')) {
            abort(403);
        }

        return Inertia::render('Vault/Index', [
            'maxUploadSize' => min(
                (int) ini_get('upload_max_filesize'),
                (int) ini_get('post_max_size')
            ),
            // Note: php_ini_loaded_file() path intentionally omitted (security: A05)
        ]);
    }

    public function list(Request $request)
    {
        // Check global permission
        if (!auth()->user()->hasRole('Super Admin') && !auth()->user()->hasPermission('media.view')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $folderId = $request->query('folder_id');
        $search = $request->query('search');
        $type = $request->query('type'); // 'image', 'document', 'all'

        $query = VaultFile::query();

        if ($folderId) {
            $query->where('folder_id', $folderId);
        } else {
            // Root
            $query->whereNull('folder_id');
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('original_name', 'like', "%{$search}%")
                    ->orWhere('alt_text', 'like', "%{$search}%");
            });
        }

        if ($type === 'image') {
            $query->where('mime_type', 'like', 'image/%');
        } elseif ($type === 'document') {
            $query->whereNot('mime_type', 'like', 'image/%');
        }

        $files = $query->orderBy('created_at', 'desc')->paginate(50);

        return response()->json($files);
    }

    public function upload(Request $request)
    {
        // Check global permission
        if (!auth()->user()->hasRole('Super Admin') && !auth()->user()->hasPermission('media.create')) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file', // Max size checked in VaultService/php.ini/config
            'folder_id' => 'nullable|string|exists:' . VaultFolder::class . ',_id',
        ]);

        $uploadedFiles = [];
        $errors = [];

        foreach ($request->file('files') as $file) {
            try {
                // If folder is provided, check write permission on folder
                if ($request->folder_id) {
                    $folder = VaultFolder::find($request->folder_id);
                    if (Gate::denies('update', $folder)) { // Using update policy for write access
                        throw new \Exception('Permission denied for folder');
                    }
                }

                $vaultFile = $this->vaultService->upload($file, $request->folder_id);
                $uploadedFiles[] = $vaultFile;
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

    /**
     * Save an AI-generated image (base64 data URI or remote URL) into the Vault.
     */
    public function saveAiImage(Request $request)
    {
        if (!auth()->user()->hasRole('Super Admin') && !auth()->user()->hasPermission('media.create')) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'image' => 'required|string',   // base64 data URI OR https:// URL
            'filename' => 'nullable|string|max:255',
            'folder_id' => 'nullable|string|exists:' . VaultFolder::class . ',_id',
        ]);

        $imageData = $request->input('image');
        $folderId = $request->input('folder_id');
        $customName = $request->input('filename');

        // Determine if base64 data URI or a remote URL
        if (str_starts_with($imageData, 'data:')) {
            // base64 data URI: data:image/png;base64,XXXX
            if (!preg_match('/^data:(image\/[a-zA-Z+]+);base64,(.+)$/', $imageData, $matches)) {
                return response()->json(['error' => 'Invalid image data URI.'], 422);
            }
            $mimeType = $matches[1];
            $extension = explode('/', $mimeType)[1] ?? 'png';
            $extension = $extension === 'jpeg' ? 'jpg' : $extension;
            $binaryData = base64_decode($matches[2]);
        } else {
            // Remote URL (e.g. OpenAI temporary URL)
            $parsedUrl = parse_url($imageData);
            if (!$parsedUrl || !isset($parsedUrl['host'])) {
                return response()->json(['error' => 'Invalid image URL format.'], 422);
            }

            try {
                $response = \App\Services\SafeHttpClient::get($imageData, 10);
                if (!$response->successful()) {
                    return response()->json(['error' => 'Failed to download remote image. Status: ' . $response->status()], 422);
                }
                $binaryData = $response->body();
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            $extension = 'png';
            $mimeType = 'image/png';
        }

        // Write to a temp file
        $tmpPath = tempnam(sys_get_temp_dir(), 'ai_vault_') . '.' . $extension;
        file_put_contents($tmpPath, $binaryData);

        $filename = $customName
            ? preg_replace('/[^a-zA-Z0-9\-_\.]/', '-', $customName) . '.' . $extension
            : 'ai-generated-' . now()->format('Ymd-His') . '.' . $extension;

        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $tmpPath,
            $filename,
            $mimeType,
            \UPLOAD_ERR_OK,
            true // test mode — skips is_uploaded_file() check
        );

        try {
            $vaultFile = $this->vaultService->upload($uploadedFile, $folderId);
            return response()->json(['file' => $vaultFile], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } finally {
            @unlink($tmpPath);
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
        $isOptimizedRequest = $file->is_optimized && !$file->use_original && $file->optimized_path;

        if ($isOptimizedRequest) {
            $storagePath = $file->optimized_path;

            // If the optimized file was trashed alongside the original, it would need '.trashed'
            // For now, our deleteFile logic only trashes the original storage_path. 
            // We should ensure the optimized file is also accessible or handled in delete, 
            // but assuming the optimized path is intact if not trashed.
            if ($file->trashed()) {
                $storagePath .= '.trashed';
            }
        }

        $path = Storage::disk($diskName)->path($storagePath);

        if (!file_exists($path)) {
            // Fallback to original if optimized is missing
            if ($isOptimizedRequest) {
                $path = Storage::disk($diskName)->path($file->storage_path);
                $isOptimizedRequest = false;
                if (!file_exists($path)) {
                    abort(404);
                }
            } else {
                abort(404);
            }
        }

        $contentType = $isOptimizedRequest ? 'image/webp' : $file->mime_type;
        $filename = $isOptimizedRequest
            ? pathinfo($file->original_name, PATHINFO_FILENAME) . '.webp'
            : $file->original_name;

        return response()->file($path, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);

        /*
        // For X-Accel-Redirect (Production optimization)
        return response()->make('', 200, [
            'Content-Type' => $file->mime_type,
            'X-Accel-Redirect' => '/internal_vault/' . basename($file->storage_path),
        ]);
        */
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
            'url' => $file->url // Return the new URL so the frontend can update instantly
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

        \App\Jobs\GenerateMissingAltTextJob::dispatch();

        return response()->json([
            'message' => 'Alt-text generation job dispatched successfully. Images will be updated in the background.'
        ]);
    }

    public function move(Request $request, string $uuid)
    {
        $file = VaultFile::where('uuid', $uuid)->firstOrFail();

        if (Gate::denies('update', $file)) {
            abort(403);
        }

        $request->validate(['folder_id' => 'nullable|string|exists:' . VaultFolder::class . ',_id']);

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
        // Check global permission
        if (!auth()->user()->hasRole('Super Admin') && !auth()->user()->hasPermission('media.view')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

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
}
