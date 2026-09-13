# Dependency Upgrade Plan — September 2026

Status: **Completed** 2026-09-13 (all 7 phases; see [Outcome](#outcome)). Branch: `chore/dependency-upgrades` off `master` (the active branch at 8357548; the remote `main` is only the initial commit).
Each phase is staged separately (no automatic commits) and must pass the verification gate before the next begins.

---

## Baseline (2026-09-13)

| Check | Result |
|---|---|
| `laravel/framework` | v13.31.0, already latest. The Aug 2026 changelog items need no action. |
| `php artisan test` | 91 passed, 219 assertions |
| PHPUnit 12.5 deprecations (`PAO_DISABLE=1 ./vendor/bin/phpunit --display-deprecations`) | **none** |
| `npx tsc --noEmit` | exit 0 |
| `composer audit` | no advisories |
| `npm audit --omit=dev` | 14 (8 high, 3 moderate, 3 low), all fixable without majors |
| Node | local 25.9, CI 24 |

Capture `npm run build` output (bundle sizes) before Phase 4 for comparison.

---

## Decisions

| Item | Decision | Why |
|---|---|---|
| **Guzzle 8** | **Out of scope (blocked)** | `laravel/socialite` 5.31 → `league/oauth1-client ^1.11`, which caps Guzzle at `^6\|^7`. Only an unreleased 2.0 lifts it. App code never imports Guzzle directly. Revisit when oauth1-client 2.0 ships. |
| **TypeScript 7** | **Deferred** | 7.0 ships without a public compiler API. The documented path is 5.9 → 6.0 → 7.0, and the new `types: []` default breaks `NodeJS.Timeout` (`Components/Settings/SettingInput.tsx:23`). No user-facing gain. |
| **Intervention Image 4** | Upgrade **and** move `OptimizeVaultImageJob` onto Laravel 13's `Illuminate\Image` | Laravel's `GdDriver`/`ImagickDriver` are built on Intervention `^4`, so the upgrade unlocks the framework API. Writing against the facade avoids hand-porting to v4's `usingDriver`/`decodePath`/`encodeUsingFormat`. |
| **TanStack Table 9** | Two steps: bump via `useLegacyTable`, then full `useTable` + `tableFeatures` | Keeps the version bump separate from the `ColumnDef<TFeatures, TData, TValue>` generics sweep. |
| **Inertia v3** | Server and client together, **after** Vite 8 | Mutual compatibility. `resolvePageComponent` comes from `laravel-vite-plugin`, so Vite lands first. |

---

## Phase 1 — Security fixes + in-range updates

Low risk. No constraint changes.

```bash
composer update                      # sanctum 4.3.3, socialite 5.31, pint, tinker, ziggy, resend-laravel 1.4, pail, sail, collision, mockery, html-to-markdown, pao 1.1.5
npm update                           # react 19.3, radix-ui 1.6.7, tailwind 4.3.3, zod 4.6.4, recharts 3.10.1, axios 1.20, shadcn 4.21, @inertiajs/react 2.3.28, fontsource 5.3
npm audit fix                        # no --force
```

- Re-run `composer audit` and `npm audit --omit=dev`. Target: 0 advisories.
- Run `./vendor/bin/pint --test`, since Pint 1.32 may add rules.
- Smoke: shadcn/radix dialogs, dropdowns, tooltips (radix 1.4 → 1.6).

## Phase 2 — `laravel/ai` ^0.5 → ^0.11

```bash
composer require laravel/ai:^0.11
```

- API verified unchanged for our call sites: `new AnonymousAgent(string $instructions, iterable $messages, iterable $tools)`,
  `prompt($prompt, array $attachments = [], $provider = null, ?string $model = null, ?int $timeout = null)`,
  `new Base64Image(string $base64, ?string $mimeType = null)`.
- Call sites: `app/Services/AiService.php` (lines ~71, 220, 244, 291, 334, 350), `app/Http/Controllers/AiController.php:174`.
- **Behavioural risk** (0.9–0.11): shared `TextGenerationLoop`, provider failover on connection errors, stream errors now throw,
  default models changed (we always pass `$activeHub->default_model`). Confirm exceptions still map to our error handling in `AiService`.
- Smoke: AI Hub text generation, image description (Base64Image), alt-text job, AI chat sidebar, SEO/meta generation.

## Phase 3 — `intervention/image` ^3 → ^4 via `Illuminate\Image`

```bash
composer require intervention/image:^4.0
```

Rewrite `app/Jobs/OptimizeVaultImageJob.php` (lines ~54–68):

```php
use Illuminate\Support\Facades\Image;

$bytes = Image::fromStorage($this->vaultFile->storage_path, $diskName)
    ->toWebp()
    ->quality(85)
    ->toBytes();

Storage::disk($diskName)->put($optimizedPath, $bytes);
```

- Remove the `Intervention\Image\ImageManager` / `Drivers\Gd\Driver` imports. The driver comes from `config('images.default')` (`IMAGE_DRIVER`, default `gd`).
- The `file_exists($fullPath)` guard can become `Storage::disk($diskName)->exists(...)`.
- Keep the `catch (\Exception $e)`. `Illuminate\Image\ImageException` is an `Exception`.
- Add a feature test: upload a PNG → dispatch job → assert `optimized_*.webp` exists and `is_optimized` is true.

## Phase 4 — Vite 8 toolchain (atomic)

```bash
npm i -D vite@^8 @vitejs/plugin-react@^6 laravel-vite-plugin@^3 @types/node@^26
npm rm @tailwindcss/vite
```

- All three plugins peer on `vite ^8`, and Node must be `^20.19 || >=22.12`.
- `vite.config.js` has no `rollupOptions` or `esbuild` block, so no config migration is needed.
- Confirm `laravel-vite-plugin/inertia-helpers` still exports `resolvePageComponent` (used in `resources/js/app.tsx:10`).
- `@tailwindcss/vite` is dead weight: Tailwind loads through `postcss.config.js` (`@tailwindcss/postcss`), and `vite.config.js` never imports it. Its peer already allows `vite ^8`, so it won't block the install. Remove it here anyway with `npm rm @tailwindcss/vite`.
- `.npmrc` sets `ignore-scripts=true`. Rolldown and Oxc ship native binaries through platform `optionalDependencies`, so no postinstall should be needed. If `npm run build` fails with a missing native binding, check `npm ls rolldown` for the `@rolldown/binding-*` package matching this platform. Don't disable `ignore-scripts`.
- Check `npm ls vite` for peer warnings.
- CSS minification now uses Lightning CSS. Visually diff the admin shell and public pages, and compare bundle sizes with the baseline.
- Update `package.json` `engines.node` to `>=22.12.0`. Phase 7 requires Node 22 anyway (react-dropzone 20, concurrently 10). CI already uses 24.
- Docs: `AGENTS.md:29`, `wiki/architecture/stack.md:16`, `wiki/frontend/ui-stack.md:13` say "Vite 7".

## Phase 5 — Inertia v3 (server + client)

```bash
composer require inertiajs/inertia-laravel:^3.0
npm i @inertiajs/react@^3
php artisan view:clear
```

Required changes (verified against v3 upgrade guide + package types):

1. `resources/views/app.blade.php:8`: `<title inertia>` → `<title data-inertia>`. `@inertia`/`@inertiaHead` keep working.
2. `resources/js/types/global.d.ts`: `PageProps` is still an augmentable interface in `@inertiajs/core@3`, so the current code compiles.
   Migrate to the v3 pattern:
   ```ts
   declare module '@inertiajs/core' {
       export interface InertiaConfig {
           sharedPageProps: AppPageProps;
       }
   }
   ```
   The local `PageProps<T>` in `types/index.d.ts` is our own type, so the 145 page files are unaffected.
3. `useForm` now resets `processing`/`progress` only in `onFinish`. Review the `onSuccess` handlers that close dialogs or reset UI:
   `Components/Users/InviteUserDialog.tsx:39`, `Pages/Settings/Index.tsx:48`, `Pages/Profile/Partials/UpdatePasswordForm.tsx:35`,
   `DeleteUserForm.tsx:41`, `Pages/Menus/Index.tsx:55`, `Pages/AiHub/Index.tsx:78`, `Pages/Pages/Edit.tsx:290`.
4. Inertia no longer bundles axios. We already depend on it directly (`bootstrap.ts`, AI components, Vault hooks), so nothing to do.

Already verified as no-ops: no `router.cancel()`, no `router.on('invalid'|'exception')`, no `.layout =` assignments,
no `hideProgress`/`revealProgress`, no `future` block, no `Inertia::lazy`, no Inertia testing traits, no SSR, no `qs`/`lodash` imports.

Smoke: every admin index + edit page, form validation errors, `router.delete` confirmations, file uploads via `useForm`,
progress bar, back/forward history, 419/500 error modal behaviour.

## Phase 6 — PHPUnit 13

```bash
composer require --dev phpunit/phpunit:^13.3 laravel/pao:^1.1.5 nunomaduro/collision:^8.9.5
```

- Gate cleared: zero PHPUnit 12.5 deprecations, no `any()` matchers, no docblock metadata.
- Laravel 13.31 allows `^13.0.3`. Collision 8.9.5 allows `<14`. pao 1.1.5 conflicts only with `>=13.0.0 <13.1.7`.
- Run `./vendor/bin/phpunit --migrate-configuration` if the XML schema warns.
- Docs: `wiki/architecture/testing.md`.

## Phase 7 — Remaining npm majors

### 7a. TanStack Table 9 via the legacy entry point
```bash
npm i @tanstack/react-table@^9
```
- 13 files. Switch `useReactTable` + `get*RowModel` imports to `@tanstack/react-table/legacy` (`useLegacyTable`).
- Usage: `useReactTable` ×4, `getCoreRowModel`, `getSortedRowModel`, `getFilteredRowModel`, `getPaginationRowModel`,
  `getFacetedRowModel`, `getFacetedUniqueValues`, `flexRender`, types `ColumnDef`, `Table`, `Column`, `Row`, `Header`,
  `SortingState`, `ColumnFiltersState`, `VisibilityState`.
- Smoke: Pages, Users, Roles, Banners, Menus, AiHub tables. Check sort, faceted filters, pagination, column visibility.

### 7b. TanStack Table 9 native API (separate stage)
- Define one shared `features = tableFeatures({ columnFilteringFeature, columnFacetingFeature, rowSortingFeature, rowPaginationFeature, columnVisibilityFeature, filteredRowModel: createFilteredRowModel(), sortedRowModel: createSortedRowModel(), paginatedRowModel: createPaginatedRowModel(), facetedRowModel: createFacetedRowModel(), facetedUniqueValues: createFacetedUniqueValues(), filterFns, sortFns })` in `Components/Common/DataTable.tsx`, and export `type AppTableFeatures = typeof features`.
- `useLegacyTable` → `useTable({ features, ... })`. `ColumnDef<T>` → `ColumnDef<AppTableFeatures, T>` (also `Table`, `Column`, `Row`, `Header`).
- `table.getState()` → `table.state`. Do not destructure instance methods (`row.getValue(...)` only).
- Decide whether `Components/data-table.tsx` and `Components/Common/DataTable.tsx` should be merged (duplicate?).

### 7c. Small majors
| Package | Change | Notes |
|---|---|---|
| `lucide-react` 0.563 → 1.x | 71 files import icons | Brand icons removed. None imported ("Facebook"/"Twitter" in `Pages/Edit.tsx` are labels). Renamed icons surface as `tsc` errors. Icons now default to `aria-hidden`, so check that icon-only buttons have `sr-only` text. |
| `react-dropzone` 15 → 20 | `Components/Vault/VaultUploadDialog.tsx` | We use `useDropzone({ onDrop })` with `File[]` and no `accept`/`maxFiles`, so the v18 `FileWithPath` and v19 batch-limit changes don't apply. Requires Node ≥ 22. |
| `@shadcn/react` 0.1 → 0.3 | `Components/ui/message-scroller.tsx` | Check the export names. Smoke the AI chat scroll. |
| `concurrently` 9 → 10 | `composer run dev` | Requires Node ≥ 22. Run `composer run dev` and confirm all 4 processes start and `--kill-others` works. |

---

## Verification gate (every phase)

```bash
./vendor/bin/pint --test          # if PHP changed
php artisan test
npx tsc --noEmit
npm run build
composer audit && npm audit --omit=dev
```

Manual smoke core (Phases 1, 4, 5, 7): login (email + Google/GitHub toggles), dashboard charts, Pages index/edit (TinyMCE, dirty state),
Vault drag-drop upload → optimization, AI Hub + chat, Settings save, Menus ordering, public page as HTML and with
`Accept: text/markdown`, `/llms.txt`.

## Rollback

Each phase is its own staged change set. Roll back with `git restore --staged --worktree composer.json composer.lock package.json package-lock.json <touched files>`
followed by `composer install && npm ci`.

## Wrap-up

- CHANGELOG `[Unreleased]`: list the upgrades plus Guzzle 8 and TypeScript 7 as deferred.
- README requirements: Node ≥ 22.12.
- Wiki: `architecture/stack.md`, `frontend/ui-stack.md`, `modules/vault.md` (`Illuminate\Image`), `modules/ai-hub.md` (laravel/ai 0.11), `architecture/testing.md`, plus a `log.md` entry.
- Follow-ups: TypeScript 6 → 7, Guzzle 8 when oauth1-client 2.0 is tagged, optional `@inertiajs/vite` plugin to replace `resolvePageComponent`.

---

## Outcome

Final gate: Pint clean, 93 tests / 225 assertions (PHPUnit 13.3.3, no deprecations), `tsc` clean, `npm run build` OK
(JS 481.95 kB, down from 513.34 kB at baseline; build ~0.8s on Vite 8.3), `composer audit` and `npm audit --omit=dev` clean.

| Package | Before | After |
|---|---|---|
| `laravel/ai` | 0.5.1 | 0.11.2 |
| `inertiajs/inertia-laravel` / `@inertiajs/react` | 2.0.24 / 2.3.18 | 3.3.4 / 3.7.1 |
| `intervention/image` | 3.11.7 | 4.3.2 |
| `phpunit/phpunit` | 12.5.26 | 13.3.3 |
| `vite` / `@vitejs/plugin-react` / `laravel-vite-plugin` | 7.3.3 / 4.7.0 / 2.1.0 | 8.3.0 / 6.1.1 / 3.2.0 |
| `@tanstack/react-table` | 8.21.3 | 9.2.4 |
| `lucide-react` / `react-dropzone` / `@shadcn/react` / `concurrently` | 0.563 / 15.0 / 0.1 / 9.2 | 1.45 / 20.1 / 0.3.1 / 10.0.5 |

Deviations from the plan:
- **Vite 8 install hit ERESOLVE.** npm resolved `@vitejs/plugin-react` 6's optional peer chain (`@rolldown/plugin-babel` → `@babel/plugin-transform-runtime` 8) to Babel 8. That clashed with the shadcn CLI's Babel 7. Fixed with `"overrides": { "@babel/plugin-transform-runtime": "^7.29.0" }` (nothing extra is installed). No `--force` or `--legacy-peer-deps`. Removing `shadcn` doesn't help, and `app.css` imports `shadcn/tailwind.css`.
- **In-range updates surfaced a recharts typing change.** Fixed the tooltip `labelFormatter` in `chart-area-interactive.tsx`.
- **Socialite 5.31 moved `phpseclib/phpseclib` 3 → 4** (transitive; no app usage). `laravel/ai` 0.11 dropped `prism-php/prism` (no app usage).
- **Inertia v3 narrowed the `resolve` type.** `app.tsx` now globs with `import.meta.glob<ResolvedComponent>(..., { import: 'default' })`.
- **TanStack v9:** went straight to the native API, skipping the `useLegacyTable` step, because the shared `DataTable` centralizes it. `DataTable` dropped its `TValue` generic (v9 column types are invariant in `TValue`). Filter/sort registries are registered in full so `'auto'` behaves as in v8. The unused `Components/data-table.tsx` shadcn block reuses the same features.
- **Vault wiki fix:** `SanitizeImage` uses native GD, not Intervention.

Still to verify manually (not covered by automated tests): social login (phpseclib 4), AI Hub/chat (laravel/ai 0.11),
admin tables (sort, faceted filters, pagination, column visibility, Users row selection), Vault drag-drop upload,
form submit/validation flows (Inertia v3 `processing` resets in `onFinish`), icon rendering (lucide 1.x), `composer run dev`.
