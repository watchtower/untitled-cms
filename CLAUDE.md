# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Untitled CMS** is an AI-native Content Management System built on Laravel 12 with MongoDB and a React + Inertia.js admin SPA. Public pages are served as HTML by default and as Markdown+YAML frontmatter when requested with `Accept: text/markdown` (for AI crawlers/agents).

## Common Commands

```bash
# Install and initialize everything
composer run setup

# Start all dev services (PHP server, queue worker, logs, Vite HMR) concurrently
composer run dev

# Run tests
composer run test

# Run a single test file
php artisan test tests/Feature/VaultUploadTest.php

# PHP code formatting
./vendor/bin/pint

# Frontend assets
npm run dev       # Vite dev server
npm run build     # tsc + vite build (TypeScript errors will fail the build)
```

**Testing notes:** PHPUnit uses SQLite in-memory for tests (overrides MongoDB). Tests in `tests/Feature/` cover Auth, Maintenance Mode, Profile, Vault upload/folder operations. The `public/hot` file is created in setUp and removed in tearDown to bypass `ViteManifestNotFoundException`.

## Architecture

### Stack
- **Backend:** Laravel 12, PHP 8.4, MongoDB (`mongodb/laravel-mongodb`)
- **Frontend:** React 18 + TypeScript, Inertia.js (props-based routing, no client-side router), Tailwind CSS, Shadcn/Radix UI
- **Build:** Vite 7 (frontend build runs `tsc && vite build`)
- **Auth:** Laravel Sanctum + Sessions, Laravel Socialite (Google, GitHub, Apple, Twitter — toggled via Settings UI)

### Request Flow
```
Browser → Laravel Route → Middleware Stack → Controller → Service/Model → MongoDB
                                                       ↓
                                              Inertia::render($page, $props) → React Page Component
```

### Service Layer (`app/Services/`)

Business logic lives here, not in controllers.

- **`AiService`** — Multi-provider AI orchestration (OpenAI, Gemini, Stability AI). Providers are configured at runtime via the AI Hub UI, not hardcoded.
- **`AiActionService`** — Structured AI-driven CMS mutations (create/update pages and banners). Actions are validated against a whitelist, resolved server-side, and are revertible via `ActivityLog` before-state snapshots.
- **`AiContextService`** — Aggregates project context (pages, settings) for AI prompts; caches to avoid redundant DB queries.
- **`VaultService`** — Media management. Entry point for all vault operations; delegates uploads to the pipe pipeline.
- **`SettingsService`** — Key/value settings with cache. Always use this instead of querying `settings` directly.
- **`ActivityLogger`** — Static `log()` call used throughout controllers to write to `activity_logs`. Fails silently to avoid disrupting user flow.
- **`SafeHttpClient`** — SSRF-protected HTTP client. Use this for all outbound requests, never the `Http` facade directly.

### Pipeline Pattern (Vault Upload)

`VaultService` runs uploads through `app/Vault/Pipes/` in order:
1. `DetectDoubleExtension` → 2. `ValidateMimeType` → 3. `SanitizeImage` → 4. `ModerationCheck` → 5. `GenerateUuid` → 6. `StoreMetadata`

State is carried via `app/Vault/DTOs/VaultPipelinePayload.php`. Upload config is in `config/vault.php` (allowed extensions, 50MB max, ClamAV toggle, `image_washing`).

### Permissions System

Permissions are strings in `resource.action` format (e.g. `pages.edit`, `media.upload`). The canonical list (~32 permissions) is defined in `Role::availablePermissions()` in `app/Models/Role.php` — this is the single source of truth.

- **`User::hasPermission(string)`** / **`User::getCachedPermissions()`** — cached in Redis/cache for 60s per user
- **`User::canAccessBackend()`** — separate cache key; gates the entire admin area (checks `backend_access` flag on roles)
- **`HasRoles` trait** — only adds `hasRole(string $slug)` helper; everything else is on the `User` model
- **8 Policy classes** in `app/Policies/` — one per resource type
- **`CheckPermission` middleware** — the `can` alias points here (not Laravel's default). Usage: `->middleware('can:pages.edit')`
- **`RequireAdminAccess` middleware** — applied to all admin routes; checks `canAccessBackend()` and redirects to `/` on failure
- Cache is busted automatically: `Role::saved` event busts all member caches; `User::syncRoles()` busts the affected user's cache

### Middleware Stack (web, in order)

1. `HandleInertiaRequests` — shares props to all pages (see below)
2. `AddLinkHeadersForPreloadedAssets` — preload `Link` headers for performance
3. `CheckRedirects` — database-driven URL redirects (hits MongoDB on every request — keep `redirects` collection indexed)
4. `CheckMaintenanceMode` — custom maintenance mode; reads from `SettingsService` (cache lag possible)

Admin routes additionally apply: `auth`, `verified`, `RequireAdminAccess`.

### Inertia Shared Props

`HandleInertiaRequests` shares on every page load:
```
auth.user              — User object
auth.permissions       — string[] from getCachedPermissions()
auth.canAccessBackend  — boolean
appName                — config('app.name')
settings               — public settings key/value (SettingsService::getPublicSettings)
tinymce_api_key        — only for backend users; null otherwise
aiChatEnabled          — boolean from settings
menus                  — Menu::active()->get()->keyBy('slug')
```

### Frontend (`resources/js/`)

- **Pages/** — one file per controller. Props typed via `PageProps<T>` generic from `types/index.d.ts`.
- **Components/ui/** — Shadcn/Radix component wrappers
- **Layouts/** — `AuthenticatedLayout`, `AuthLayout`, `GuestLayout`, `PublicLayout`
- **types/index.d.ts** — `PageProps<T>` generic; extend it for page-specific props
- **`route()`** — Ziggy-generated typed route helper, available globally

Key UI libraries: TanStack Table (data grids), @dnd-kit (drag-drop), Recharts (analytics charts), Zod (form validation), Sonner (toasts), TinyMCE (`Editor.tsx`) for rich content, `react-dropzone` for Vault uploads.

Inertia form pattern: use `useForm()` from `@inertiajs/react` — handles loading state, errors, and submission. No fetch calls or separate API layer.

### AI-Native Endpoints (no auth)

- `GET /llms.txt` — llmstxt.org standard index of published pages (plain text)
- `GET /llms-full.txt` — full content of all published pages as Markdown; includes `x-llms-tokens` header
- `GET /sitemap.md` — sitemap for AI agents
- `GET /{slug}` with `Accept: text/markdown` — individual page as Markdown + YAML frontmatter

### Route Structure (`routes/web.php`)

- **Public (no auth):** `/`, `/rss`, `/feed`, `/sitemap.md`, `/llms.txt`, `/llms-full.txt`, `/{slug}`
- **Profile:** `auth` only (no admin middleware)
- **Admin:** `auth` + `verified` + `RequireAdminAccess` — all resource controllers
- **AI text generation:** `throttle:30,1`
- **AI image generation:** `throttle:10,1`
- **AI chat + actions:** `throttle:60,1`

## Project Wiki

A persistent, LLM-maintained knowledge base lives in `wiki/`. Open the `wiki/` directory as an Obsidian vault for graph view and backlink navigation.

- `wiki/SCHEMA.md` — how to ingest sources, query, update, and lint the wiki
- `wiki/index.md` — content catalog with links to all pages
- `wiki/log.md` — append-only history of wiki operations

Pages are organized under `architecture/`, `database/`, `frontend/`, and `modules/`. Use `[[folder/page]]` wiki-link syntax for cross-references.

## Database

MongoDB is required for production. Tests override to SQLite in-memory.

```env
DB_CONNECTION=mongodb
DB_HOST=127.0.0.1
DB_PORT=27017
DB_DATABASE=untitled_cms
```

All models set `protected $connection = 'mongodb'` and `protected $collection = 'name'`. Use `mongodb/laravel-mongodb` relationship methods — standard Eloquent relationship internals differ.

Key collections: `users`, `roles`, `pages`, `banners`, `vault_files`, `vault_folders`, `activity_logs`, `ai_hubs`, `chat_sessions`, `menus`, `settings`, `redirects`, `email_logs`, `suppressed_emails`.
