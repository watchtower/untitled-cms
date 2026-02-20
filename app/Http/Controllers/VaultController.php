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
        if (! auth()->user()->hasRole('Super Admin') && ! auth()->user()->hasPermission('media.view')) {
            abort(403);
        }

        return Inertia::render('Vault/Index', [
            'maxUploadSize' => min(
                (int) ini_get('upload_max_filesize'),
                (int) ini_get('post_max_size')
            ),
            'phpIniPath' => php_ini_loaded_file(),
        ]);
    }

    public function list(Request $request)
    {
        // Check global permission
        if (! auth()->user()->hasRole('Super Admin') && ! auth()->user()->hasPermission('media.view')) {
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
        if (! auth()->user()->hasRole('Super Admin') && ! auth()->user()->hasPermission('media.create')) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file', // Max size checked in VaultService/php.ini/config
            'folder_id' => 'nullable|string|exists:'.VaultFolder::class.',_id',
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

        $path = Storage::disk($diskName)->path($storagePath);

        if (! file_exists($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => $file->mime_type,
            'Content-Disposition' => 'inline; filename="'.$file->original_name.'"',
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
        // Check global permission
        if (! auth()->user()->hasRole('Super Admin') && ! auth()->user()->hasPermission('media.view')) {
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
