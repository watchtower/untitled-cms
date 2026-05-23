# Vault Improvements Implementation Plan

Source reference: `internationalfriends2/features/MediaVault` branch  
Target: `untitled-cms` — existing Vault implementation

---

## Impact Sentinel Audit (2026-04-13)

### Critical Bug Fixed
`VaultController::serve()` had a missing closing `}` and two undefined variables (`$contentType`, `$filename`). This caused HTTP 500 on the entire routing stack. **Fixed** — routing restored to 200.

### Already Implemented (remove from backlog)
After reading the actual code, these plan items are already done:
- ✅ Phase 1.1 — cascade slug + physical relocation + rollback (`VaultService::cascadeFolderUpdate`)
- ✅ Phase 1.1 — folder `moveFolder()` service method + `PATCH /folders/{id}/move` route
- ✅ Phase 1.2 — VaultFolderPermission enforced in `VaultFolderPolicy`
- ✅ Phase 3.1 — `/media/{uuid}` CDN endpoint (throttle:1000,1, outside auth middleware)
- ✅ Phase 3.2 — RFC 7232 headers (`ETag`, `Last-Modified`, `Cache-Control`) on `serve()`
- ✅ Phase 4.2 — Folder `restore()` and `forceDestroy()` endpoints
- ✅ Phase 4.3 — `batchRestore` and `emptyTrash` endpoints
- ✅ Phase 5.1 — `checkDuplicate` server-side endpoint
- ✅ Phase 6.3 — Folder size aggregation via MongoDB `$group` pipeline
- ✅ Phase 2.1/2.2 — `PruneVaultSandbox` job and `vault:purge` command files exist

### Known Query Risks (to address)
- **N+1 in policies**: `VaultFolderPolicy` runs 2 queries per call; multiplies when folder list is rendered
- **N+1 in cascade**: `cascadeFolderUpdate` calls `$file->folder->path_slug` inside chunk loop
- **Missing text index**: `buildListQuery` uses `like '%search%'` on `original_name` + `alt_text` without an index

---

## Overview

This plan tracks remaining improvements. Items already implemented are marked above.
Items are ordered by priority: correctness first, then performance, then UX.

---

## Phase 1 — Data Integrity (do first, blockers)

### 1.1 Cascade slug + physical relocation on folder rename/move

**Problem:** `VaultService::renameFolder()` updates the folder's own `name` but does not regenerate `path_slug` for descendants, and does not physically move files on disk. Renamed folders leave orphaned files.

**Changes:**
- `app/Services/VaultService.php` — rewrite `renameFolder()` and add `moveFolder()`
  - After updating the folder record, collect all descendant folders via recursive query
  - Recompute each descendant's `path_slug` from its new ancestry chain
  - For each affected `VaultFile`, move the physical file from old `storage_path` to new path
  - Wrap physical moves in a try/catch: on DB failure, reverse all disk moves before rethrowing
- Add `PATCH /admin/vault/folders/{id}/move` route + `VaultFolderController::move()` action

**Transaction-like safety pattern:**
```php
$moved = [];
try {
    foreach ($filesToMove as $file) {
        Storage::disk('vault')->move($file->storage_path, $newPath);
        $moved[] = [$file->storage_path, $newPath];
        $file->update(['storage_path' => $newPath]);
    }
} catch (\Throwable $e) {
    foreach (array_reverse($moved) as [$from, $to]) {
        Storage::disk('vault')->move($to, $from); // reverse
    }
    throw $e;
}
```

---

### 1.2 VaultFolderPermission enforcement in policies

**Problem:** `VaultFolderPolicy` and `VaultFilePolicy` fall back to global `media.*` permissions even when `VaultFolderPermission` records exist for a folder. Per-folder access control is not enforced.

**Changes:**
- `app/Policies/VaultFolderPolicy.php` — for `view`, `update`, `delete`: check `vault_folder_permissions` collection first; if records exist for this folder, require matching `user_id` or `role_id` + `permission` field; only fall back to global if no records exist
- `app/Policies/VaultFilePolicy.php` — delegate to folder policy when `$file->folder_id` is set
- `app/Http/Controllers/Admin/VaultFolderController.php::list()` — flag restricted folders in response (`is_restricted: true`) so frontend can grey them out

---

## Phase 2 — Maintenance Jobs (prevent disk exhaustion)

### 2.1 `PruneVaultSandbox` job

**Problem:** Failed or abandoned uploads leave temp files in `vault_sandbox` disk indefinitely.

**Changes:**
- Create `app/Jobs/PruneVaultSandbox.php`
  - Scan `storage/app/vault_sandbox/` for files older than 1 hour
  - Delete each and log count
- Register in `routes/console.php` (or `Console/Kernel.php`):
  ```php
  Schedule::job(new PruneVaultSandbox)->daily();
  ```

---

### 2.2 `vault:purge` Artisan command

**Problem:** Soft-deleted files accumulate in the `vault_files` collection and on disk with no cleanup mechanism.

**Changes:**
- Create `app/Console/Commands/VaultPurge.php`
  - Signature: `vault:purge {--days=30}`
  - Query `VaultFile::onlyTrashed()->where('deleted_at', '<', now()->subDays($days))`
  - For each: remove physical file + call `forceDelete()`
  - Output count of purged files
- Register in `routes/console.php`:
  ```php
  Schedule::command('vault:purge --days=30')->daily()->at('02:00');
  ```

---

## Phase 3 — Performance (CDN + HTTP caching)

### 3.1 CDN-friendly public file endpoint

**Problem:** Public files are served through the admin `serve()` endpoint which attaches session middleware, preventing Cloudflare from caching responses.

**Changes:**
- `routes/web.php` — add a new route group **outside** the `auth` + `web` middleware groups:
  ```php
  Route::get('/media/{uuid}', [VaultController::class, 'servePublic'])
      ->middleware('throttle:300,1')
      ->name('vault.public');
  ```
- `app/Http/Controllers/Admin/VaultController.php` — add `servePublic()`:
  - Lookup `VaultFile` by uuid where `is_public = true` and `deleted_at = null`
  - Abort 404 if not found or trashed
  - Serve from `public` disk with RFC 7232 headers (see 3.2)
  - No auth check — public files are intentionally open
- Update `VaultFile::$appends` `url` attribute to return `/media/{uuid}` for public files

---

### 3.2 RFC 7232 conditional headers on `serve()`

**Problem:** The `serve()` endpoint returns full file content on every request with no caching headers, wasting bandwidth for unchanged files.

**Changes:**
- `app/Http/Controllers/Admin/VaultController.php::serve()` — add before streaming:
  ```php
  $etag = md5($file->updated_at . $file->size_bytes);
  $lastModified = $file->updated_at->toRfc7231String();

  if (request()->header('If-None-Match') === "\"{$etag}\"") {
      return response('', 304);
  }

  return response()->file($path, [
      'ETag'          => "\"{$etag}\"",
      'Last-Modified' => $lastModified,
      'Cache-Control' => $file->is_public ? 'public, max-age=31536000' : 'private, no-store',
  ]);
  ```

---

## Phase 4 — Missing CRUD endpoints

### 4.1 Folder move endpoint

Covered in 1.1 — `PATCH /admin/vault/folders/{id}/move` + `VaultFolderController::move()`.

---

### 4.2 Folder force-delete + restore

**Changes:**
- `app/Http/Controllers/Admin/VaultFolderController.php`
  - Add `restore(VaultFolder $folder)` — blocks if parent folder is also trashed
  - Add `forceDestroy(VaultFolder $folder)` — blocks if folder has children or files; calls `VaultService::purgeFolder()`
- `routes/web.php`:
  ```php
  Route::post('/folders/{id}/restore', [VaultFolderController::class, 'restore']);
  Route::delete('/folders/{id}/force', [VaultFolderController::class, 'forceDestroy']);
  ```

---

### 4.3 Batch restore files + empty trash

**Changes:**
- `app/Http/Controllers/Admin/VaultController.php`
  - Add `batchRestore(Request $request)` — accepts `uuids[]`, restores each via `VaultService::restoreFile()`
  - Add `emptyTrash()` — deletes all `VaultFile::onlyTrashed()` owned by current user (or all if `media.delete` permission); throttled 5/min
- `routes/web.php`:
  ```php
  Route::post('/files/batch-restore', [VaultController::class, 'batchRestore'])->middleware('throttle:30,1');
  Route::delete('/trash', [VaultController::class, 'emptyTrash'])->middleware('throttle:5,1');
  ```

---

## Phase 5 — Duplicate Detection

### 5.1 Server-side duplicate check endpoint

**Changes:**
- `app/Http/Controllers/Admin/VaultController.php` — add `checkDuplicate(Request $request)`:
  ```php
  $file = VaultFile::where('hash_sha256', $request->hash)
      ->whereNull('deleted_at')
      ->with('folder')
      ->first();

  return response()->json([
      'isDuplicate' => (bool) $file,
      'file'        => $file?->only(['uuid', 'original_name', 'created_at', 'folder']),
  ]);
  ```
- `routes/web.php`:
  ```php
  Route::get('/vault/check-duplicate', [VaultController::class, 'checkDuplicate'])->middleware('throttle:120,1');
  ```

---

### 5.2 Client-side SHA-256 hashing in `VaultUploadDialog.tsx`

**Changes:**
- `resources/js/Components/VaultUploadDialog.tsx` — before queuing each file for upload:
  ```ts
  async function hashFile(file: File): Promise<string> {
      const buffer = await file.arrayBuffer();
      const digest = await crypto.subtle.digest('SHA-256', buffer);
      return Array.from(new Uint8Array(digest))
          .map(b => b.toString(16).padStart(2, '0'))
          .join('');
  }
  ```
  - After hashing, call `GET /admin/vault/check-duplicate?hash={hash}`
  - If duplicate found: show inline warning with existing file name, folder, and upload date
  - Give user two options: **Skip** or **Upload anyway**
- Add session-level deduplication: track hashes of files already queued in current batch; warn if same hash appears twice

---

## Phase 6 — Frontend UX

### 6.1 `useVaultImagePicker` composable

**Problem:** Every admin form that needs a file picker must manually wire Inertia `useForm` + VaultPicker state. Duplicated across many pages.

**Changes:**
- Create `resources/js/composables/useVaultImagePicker.ts`:
  ```ts
  export function useVaultImagePicker(form: InertiaForm<any>, field: string) {
      const openPicker = inject<(opts: VaultPickerOptions) => void>('openVaultPicker');

      function pick() {
          openPicker?.({
              mode: 'single',
              onSelect: (file: VaultFile) => {
                  form[field] = file.url;
              },
          });
      }

      function clear() {
          form[field] = null;
      }

      return { pick, clear, url: computed(() => form[field]) };
  }
  ```
- `resources/js/Layouts/AuthenticatedLayout.tsx` — mount `VaultPicker` globally and `provide('openVaultPicker', open)` so the composable works anywhere in the tree

---

### 6.2 Error stage highlighting in `UploadPipelineTracker.tsx`

**Problem:** When a pipeline stage fails, the tracker doesn't visually indicate which stage caused the error.

**Changes:**
- `resources/js/Components/UploadPipelineTracker.tsx`
  - Accept an optional `errorStage?: string` prop
  - When set, highlight that stage row in red and show the error message inline below it
  - All stages after the error stage should be shown as skipped (greyed out)

---

### 6.3 Folder size aggregation in folder listing

**Changes:**
- `app/Http/Controllers/Admin/VaultFolderController.php::list()` — add MongoDB aggregation:
  ```php
  $sizes = VaultFile::raw(fn($col) => $col->aggregate([
      ['$match' => ['folder_id' => ['$in' => $folderIds], 'deleted_at' => null]],
      ['$group' => ['_id' => '$folder_id', 'total' => ['$sum' => '$size_bytes']]],
  ]))->pluck('total', '_id');
  ```
  - Attach `size_bytes` to each folder in the response
- `resources/js/Pages/Vault/Index.tsx` — display formatted size (`1.2 MB`) below file count in folder sidebar

---

## Phase 7 — ClamAV (security hardening, optional)

### 7.1 Wire `SandboxedScan` pipe

**Problem:** `app/Vault/Pipes/SandboxedScan.php` is a stub that logs but does not scan.

**Changes:**
- `composer.json` — add `sunspikes/clamav-validator`
- `app/Vault/Pipes/SandboxedScan.php` — implement scan using `Sunspikes\ClamavValidator\ClamavValidator`:
  ```php
  if (config('vault.clamav_enabled')) {
      $validator = new ClamavValidator($payload->file->getRealPath());
      if (! $validator->passes()) {
          $payload->validation_status = 'infected';
          // Do not throw — mark and let StoreMetadata proceed; controller can block display
      }
  }
  ```
- `config/vault.php` — already has `clamav_enabled` and `clamav_timeout` flags; no changes needed

---

## File Change Summary

| File | Change type |
|---|---|
| `app/Services/VaultService.php` | Update `renameFolder()`, add `moveFolder()` with cascade + rollback |
| `app/Policies/VaultFilePolicy.php` | Enforce `VaultFolderPermission` records |
| `app/Policies/VaultFolderPolicy.php` | Enforce `VaultFolderPermission` records |
| `app/Http/Controllers/Admin/VaultController.php` | Add `servePublic`, `checkDuplicate`, `batchRestore`, `emptyTrash`; update `serve()` with RFC 7232 |
| `app/Http/Controllers/Admin/VaultFolderController.php` | Add `move`, `restore`, `forceDestroy`; update `list()` with size aggregation |
| `app/Jobs/PruneVaultSandbox.php` | New |
| `app/Console/Commands/VaultPurge.php` | New |
| `app/Vault/Pipes/SandboxedScan.php` | Implement ClamAV scan |
| `routes/web.php` | Add `/media/{uuid}`, `/vault/check-duplicate`, batch-restore, empty-trash, folder move/restore/force routes |
| `routes/console.php` | Schedule `PruneVaultSandbox` + `vault:purge` |
| `resources/js/Components/VaultUploadDialog.tsx` | SHA-256 hashing, duplicate warning, session dedup |
| `resources/js/Components/UploadPipelineTracker.tsx` | Error stage highlighting |
| `resources/js/composables/useVaultImagePicker.ts` | New |
| `resources/js/Layouts/AuthenticatedLayout.tsx` | Global VaultPicker provide |
| `resources/js/Pages/Vault/Index.tsx` | Display folder size |
