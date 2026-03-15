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

# PHP code formatting
./vendor/bin/pint

# Frontend assets
npm run dev       # Vite dev server
npm run build     # Production build
```

**Run a single test file:**
```bash
php artisan test tests/Feature/VaultUploadTest.php
```

**Testing notes:** PHPUnit uses SQLite in-memory for tests (overrides MongoDB). Tests in `tests/Feature/` cover Auth, Maintenance Mode, Profile, Vault upload/folder operations.

## Architecture

### Stack
- **Backend:** Laravel 12, PHP, MongoDB (`mongodb/laravel-mongodb`)
- **Frontend:** React 18 + TypeScript, Inertia.js (props-based routing, no client-side router), Tailwind CSS, Shadcn/Radix UI
- **Build:** Vite 7
- **Auth:** Laravel Sanctum + Sessions

### Request Flow
```
Browser → Laravel Route → Controller → Service/Model → MongoDB
                                    ↓
                           Inertia Response → React Page Component
```

### Key Patterns

**Service Layer** — Business logic lives in `app/Services/`, not controllers:
- `AiService` — Multi-provider AI orchestration (OpenAI, Gemini, Stability AI)
- `VaultService` — Media management
- `AiContextService` — Context aggregation for AI (caches DB queries)
- `SettingsService` — Key/value settings with cache
- `SafeHttpClient` — SSRF-protected HTTP client (use this for all outbound requests)

**Pipeline Pattern (Vault Upload)** — `VaultService` runs uploads through `app/Vault/Pipes/`:
1. `DetectDoubleExtension` → 2. `ValidateMimeType` → 3. `SanitizeImage` → 4. `ModerationCheck` → 5. `GenerateUuid` → 6. `StoreMetadata`

Carries typed data via `app/Vault/DTOs/VaultPipelinePayload.php`.

**Policy-Based Authorization** — 8 Policy classes in `app/Policies/`. The `can` middleware alias points to `CheckPermission` (custom, not Laravel default). Permissions are cached in `HasRoles` trait.

**MongoDB Models** — All models set `protected $connection = 'mongodb'` and `protected $collection = 'name'`. Use `mongodb/laravel-mongodb` relationship methods.

### Middleware Stack (web)
1. `HandleInertiaRequests` — Shares `auth`, `permissions`, `menus`, `settings` as Inertia props
2. `AddLinkHeadersForPreloadedAssets`
3. `CheckRedirects` — Database-driven URL redirects
4. `CheckMaintenanceMode` — Custom maintenance mode with admin bypass

### Route Structure (`routes/web.php`)
- **Public (no auth):** `/`, `/feed`, `/sitemap.md`, `/{slug}`
- **Authenticated** (middleware: `auth`, `verified`): All admin routes (users, roles, pages, banners, menus, vault, AI hub, settings, activity log)
- **AI endpoints** are rate-limited: 30/min for text generation, 10/min for image generation

### Frontend Pages (`resources/js/Pages/`)
Each page corresponds to a controller. Inertia passes data as props — no separate API calls needed. Key UI patterns: TanStack Table for data grids, @dnd-kit for drag-drop (banners, vault), Recharts for dashboard analytics, Zod for form validation, Sonner for toast notifications.

## Database

MongoDB is required for production. Default `.env` uses SQLite (for testing only).

```env
DB_CONNECTION=mongodb
DB_HOST=127.0.0.1
DB_PORT=27017
DB_DATABASE=untitled_cms
```

Key collections: `users`, `roles`, `pages`, `banners`, `vault_files`, `vault_folders`, `activity_logs`, `ai_hubs`, `chat_sessions`, `menus`, `settings`, `redirects`.

## Vault (Media Manager)

Upload config lives in `config/vault.php` (allowed MIME types, 50MB max size, ClamAV toggle). ClamAV is optional (`CLAMAV_ENABLED=false` by default). Image sanitization via Intervention Image is on by default (`image_washing = true`).

## AI Hub

AI providers (OpenAI, Gemini, Stability AI) are configured at runtime via the admin AI Hub UI. Monthly usage is tracked per provider. AI config is patched dynamically — do not hardcode API keys in config files.

## Permissions System

Permissions are strings in the format `resource.action` (e.g., `pages.edit`, `media.upload`, `users.delete`). The full list of ~20 permissions is defined in `app/Traits/HasRoles.php`. Permissions are cached per user with a configurable TTL.
