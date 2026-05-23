<?php

namespace App\Http\Controllers;

use App\Models\VaultFile;
use App\Models\VaultFolder;
use App\Services\VaultService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class VaultFolderController extends Controller
{
    protected $vaultService;

    public function __construct(VaultService $vaultService)
    {
        $this->vaultService = $vaultService;
    }

    public function list(Request $request)
    {
        if (! auth()->user()->hasPermission('media.view')) {
            abort(403);
        }

        $parentId = $request->query('parent_id') ?? null;

        $folders = VaultFolder::with(['owner', 'permissions'])->where('parent_id', $parentId)
            ->orderBy('name')
            ->get();

        $folderIds = $folders->pluck('_id')->map(fn ($id) => (string) $id)->toArray();

        // MongoDB aggregation for file counts and sizes
        $rawStats = VaultFile::raw(function ($collection) use ($folderIds) {
            return $collection->aggregate([
                ['$match' => ['folder_id' => ['$in' => $folderIds], 'deleted_at' => null]],
                ['$group' => [
                    '_id' => '$folder_id',
                    'files_count' => ['$sum' => 1],
                    'files_size' => ['$sum' => '$size_bytes'],
                ]],
            ]);
        });

        $filesStats = collect($rawStats)->keyBy('_id');

        $folders->transform(function (VaultFolder $folder) use ($filesStats) {
            $stat = $filesStats->get((string) $folder->_id);

            $folder->files_count = $stat ? (int) $stat['files_count'] : 0;
            $folder->files_size = $stat ? (int) $stat['files_size'] : 0;
            // Mark restricted folders so frontend can grey them out (Phase 1.2)
            $folder->is_restricted = $folder->permissions->isNotEmpty();
            $folder->makeHidden('permissions');

            return $folder;
        });

        return response()->json($folders);
    }

    public function store(Request $request)
    {
        if (! auth()->user()->hasPermission('media.create')) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|string',
        ]);

        if ($request->parent_id) {
            $parent = VaultFolder::findOrFail($request->parent_id);
            if (Gate::denies('update', $parent)) {
                abort(403);
            }
        }

        // Prevent folder name collisions within the same parent
        $exists = VaultFolder::where('parent_id', $request->parent_id)
            ->where('name', $request->name)
            ->exists();
        if ($exists) {
            return response()->json(['error' => 'A folder with this name already exists in this directory.'], 422);
        }

        $folder = $this->vaultService->createFolder(
            $request->name,
            $request->parent_id,
            Auth::id()
        );

        return response()->json($folder);
    }

    public function rename(Request $request, string $id)
    {
        $folder = VaultFolder::findOrFail($id);

        if (Gate::denies('update', $folder)) {
            abort(403);
        }

        $request->validate(['name' => 'required|string|max:255']);

        // Prevent folder name collisions within the same parent
        $exists = VaultFolder::where('parent_id', $folder->parent_id)
            ->where('name', $request->name)
            ->where('_id', '!=', $folder->id)
            ->exists();
        if ($exists) {
            return response()->json(['error' => 'A folder with this name already exists in this directory.'], 422);
        }

        $this->vaultService->renameFolder($folder, $request->name);

        return response()->json($folder->fresh());
    }

    public function move(Request $request, string $id)
    {
        $folder = VaultFolder::findOrFail($id);

        if (Gate::denies('update', $folder)) {
            abort(403);
        }

        $request->validate([
            'parent_id' => 'nullable|string',
        ]);

        if ($request->parent_id) {
            $destination = VaultFolder::findOrFail($request->parent_id);
            if (Gate::denies('update', $destination)) {
                abort(403, 'Permission denied for destination folder');
            }
        }

        // Prevent folder name collisions within the target parent
        $exists = VaultFolder::where('parent_id', $request->parent_id)
            ->where('name', $folder->name)
            ->where('_id', '!=', $folder->id)
            ->exists();
        if ($exists) {
            return response()->json(['error' => 'A folder with the same name already exists in the destination directory.'], 422);
        }

        try {
            $this->vaultService->moveFolder($folder, $request->parent_id);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json($folder->fresh());
    }

    public function restore(string $id)
    {
        $folder = VaultFolder::onlyTrashed()->findOrFail($id);

        if (Gate::denies('delete', $folder)) {
            abort(403);
        }

        // Phase 4.2: Block if parent folder is also trashed
        if ($folder->parent_id) {
            $parent = VaultFolder::withTrashed()->find($folder->parent_id);
            if ($parent && $parent->trashed()) {
                return response()->json(['error' => 'Cannot restore folder while parent is trashed'], 422);
            }
        }

        $this->vaultService->restoreFolder($folder);

        return response()->json(['message' => 'Restored successfully']);
    }

    public function destroy(string $id)
    {
        $folder = VaultFolder::findOrFail($id);

        if (Gate::denies('delete', $folder)) {
            abort(403);
        }

        // Block soft delete if not empty (MVP rule)
        if ($folder->children()->exists() || $folder->files()->exists()) {
            return response()->json(['error' => 'Folder is not empty'], 422);
        }

        $this->vaultService->deleteFolder($folder);

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function forceDestroy(string $id)
    {
        $folder = VaultFolder::withTrashed()->findOrFail($id);

        if (Gate::denies('delete', $folder)) {
            abort(403);
        }

        $this->vaultService->purgeFolder($folder);

        return response()->json(['message' => 'Folder and all contents purged successfully']);
    }
}
