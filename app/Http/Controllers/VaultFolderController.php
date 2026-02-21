<?php

namespace App\Http\Controllers;

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
        // Simple tree retrieval
        // For efficiency in a large tree, we might only fetch the current level.
        // But for a CMS media library, fetching a flattened list and building tree in JS is often fine
        // or fetching root folders and their children.

        // Let's return root folders with 'children' relation loaded recursively
        // MongoDB allows this, but standard Eloquent 'with' might be heavy.

        $parentId = $request->query('parent_id') ?? null;

        $folders = VaultFolder::with(['owner', 'permissions'])->where('parent_id', $parentId)
            ->orderBy('name')
            ->get();

        $folderIds = $folders->pluck('_id')->toArray();
        $filesStats = \App\Models\VaultFile::whereIn('folder_id', $folderIds)
            ->get(['folder_id', 'size_bytes'])
            ->groupBy('folder_id');

        $folders->transform(function (VaultFolder $folder) use ($filesStats) {
            $folderFiles = $filesStats->get((string) $folder->_id, collect());

            $folder->files_count = $folderFiles->count();
            $folder->files_size = $folderFiles->sum('size_bytes');
            $folder->is_restricted = $folder->permissions->isNotEmpty();
            unset($folder->permissions);

            return $folder;
        });

        return response()->json($folders);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('Super Admin') && !auth()->user()->hasPermission('media.create')) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|string|exists:' . VaultFolder::class . ',_id',
        ]);

        // Check permission on parent
        if ($request->parent_id) {
            $parent = VaultFolder::findOrFail($request->parent_id);
            if (Gate::denies('create', $parent)) {
                abort(403);
            }
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

        $this->vaultService->renameFolder($folder, $request->name);

        return response()->json($folder);
    }

    public function destroy(string $id)
    {
        $folder = VaultFolder::findOrFail($id);

        if (Gate::denies('delete', $folder)) {
            abort(403);
        }

        // Block delete if not empty
        if ($folder->children()->exists() || $folder->files()->exists()) {
            return response()->json(['error' => 'Folder is not empty'], 422);
        }

        $this->vaultService->deleteFolder($folder);

        return response()->json(['message' => 'Deleted successfully']);
    }
}
