# Wiki Log

Append-only record of wiki operations. Format: `## [YYYY-MM-DD] <op> | <title>`

---

## [2026-05-23] fix | Setting::get() cache serialization
Impact Sentinel detected `Setting::get()` caching full Eloquent model objects, which broke under
the new `serializable_classes => false` config (incomplete object warnings on every request).
Refactored to cache the coerced scalar value instead of the model instance. Flushed stale cache.
Also bumped `laravel/framework` from v13.4.0 → v13.11.2 (latest). 56 tests pass, zero warnings.

## [2026-05-23] update | Sync with laravel/laravel v13.7.0 Skeleton
Applied skeleton changes from v13.0.0→v13.7.0:
- Added `laravel/pao ^1.0.6` (PHP Agent-Optimized Output) — auto-formats PHPUnit/Artisan output as compact JSON when running inside AI agents.
- Created `.npmrc` with `ignore-scripts=true` and `audit=true` (v13.1.2 security hardening against malicious postinstall scripts).
- Updated `.gitignore`: removed `/.fleet`, added `/.codex`, `/.cursor/`, `/public/fonts-manifest.dev.json`, `_ide_helper.php`.
- Updated `.editorconfig`: compose file glob now covers `docker-compose.{yml,yaml}` naming variants.
- Deferred: `@no_additional_args` in composer test script (requires Composer ≥2.8; we're on 2.7.6).
- Deferred: Vite font plugin migration (v13.5.0) — we use `@fontsource-variable/*` packages instead.

## [2026-05-23] update | Laravel 13 Upgrade Hardening
Verified Laravel 13.4.0 framework compatibility and applied recommended upgrade changes:
- Bumped `phpunit/phpunit` from `^11.5.3` to `^12.0` (locked at 12.5.26). All 56 tests pass.
- Added `serializable_classes => false` to `config/cache.php` for deserialization attack hardening.
- Verified no `new static()` in model boot methods, no `Str::createUuidsUsing` in tests.
- Cache prefix already uses L13 hyphenated format. `PreventRequestForgery` middleware is active via framework defaults.
- `nunomaduro/collision ^8.6` remains compatible. `laravel/ai ^0.5` (v0.5.1) still pre-release.

## [2026-05-23] update | Shadcn Preset & React 19
Updated UI stack documentation and dependencies:
- Upgraded React to v19 in package.json and reflected the change in CLAUDE.md, wiki/architecture/stack.md, and wiki/frontend/ui-stack.md.
- Initialized Shadcn with the custom b2fA preset and unified radix imports.
- Created a baseline restore point for the Tailwind v4 + React 19 + b2fA UI state.

## [2026-05-23] update | Tailwind v4 & Shadcn Preset Migration
Migrated the front-end style stack from Tailwind CSS v3 to v4 and updated the shadcn setup:
- Run `@tailwindcss/upgrade` to move dependency packages and translate stylesheet settings to native CSS `@theme`.
- Unified 20 individual `@radix-ui/react-*` dependencies under a single `radix-ui` library.
- Updated `components.json` and regenerated/overwrote all 36 components under `resources/js/Components/ui/` with modern v4 registry code.
- Fixed TypeScript errors in `resizable.tsx` and `UploadPipelineTracker.tsx`.
- Updated [[frontend/ui-stack]] with the new stack information.

## [2026-05-23] update | Media Vault Security Gates & Scaling
Improved security, query performance, and scaling across the Media Vault:
- Added sibling unique name checks on folder create, rename, and move to prevent path/slug collisions.
- Added destination folder authorization checks to prevent folder move privilege bypasses.
- Added strict fail-closed toggle options on ClamAV scanner timeouts or daemon connectivity outages.
- Eager-loaded the folder relationship in file listings to resolve N+1 database queries.
- Optimised the Artisan purge command via chunking to maintain a low and constant memory footprint.
- Added client memory protection in upload dialog, bypassing pre-upload hashing on files larger than 10MB.
- Updated [[modules/vault]] to document the dynamic scanning stages and fail-closed options.

## [2026-04-13] feat | Media Vault Hardening & Optimizations
Implemented [[docs/vault-improvements-plan.md]].
- Phase 1: Cascade slugs and physical relocation for folder rename/move in `VaultService`.
- Phase 2: Added `PruneVaultSandbox` job and `vault:purge` Artisan command.
- Phase 3: Added CDN-friendly `/media/{uuid}` route and RFC 7232 headers for caching.
- Phase 4: Added batch-restore, empty-trash, and folder force-delete endpoints.
- Phase 5: Implemented client-side SHA-256 duplicate detection with `VaultUploadDialog` warnings.
- Phase 6: Created `useVaultPicker` React hook for global media selection.


## [2026-04-12] feat | OpenRouter & AI Hub Security Refactor
Integrated OpenRouter as a supported AI provider for text generation and vision. 
Implemented `clear_key` explicit API key revocation UI within the AI Hub dashboard.
Optimized `AiContextService` to prevent unconditional database context loading out of scope.

## [2026-04-06] refactor | Multi-provider Email Abstraction
Abstracted all email provider logic into [[Services/EmailWebhooks/Contracts/WebhookProvider]].
Created Support for Resend (Svix), Mailgun (HMAC), and SendGrid (ECDSA).
Renamed `resend_id` → `provider_message_id` across `email_logs` and updated indexes.
Unified webhook endpoint to `/webhooks/email` with generic middleware/job.
Restored legacy SDK compatibility while centralizing config under `services.email_webhook`.

## [2026-04-05] init | Wiki created from CLAUDE.md seed
Scaffolded wiki structure. Created SCHEMA.md, index.md, log.md, and 9 seed pages
derived from CLAUDE.md: overview, architecture, services, vault-pipeline, permissions,
frontend, database, middleware, ai-hub, testing.

## [2026-04-05] update | Resend email integration implemented
Created [[modules/email]]: full documentation of the listener pipeline
(StopSuppressedEmail → InjectUnsubscribeHeaders → LogSentEmail), webhook
verification, suppression model, unsubscribe flow, stats caching, and
EmailLogPolicy registration. Updated [[database/collections]] with email_logs
and suppressed_emails. Updated [[architecture/middleware]] with resend.webhook
alias and event auto-discovery note (withEvents discover:false).

## [2026-04-05] ingest | Resend.com mail integration brainstorm
Digested `docs/brainstorm-resend-integration.md`. Key decisions captured: credentials
in `.env` only (RESEND_KEY, RESEND_WEBHOOK_SECRET), MongoDB TTL 90d (EMAIL_LOG_TTL_DAYS),
webhook handler queued as ProcessResendWebhook job (updateOrCreate race-safety), CTR as
primary AI signal, HMAC-signed unsubscribe tokens, List-Unsubscribe header required.
New wiki page to create: [[modules/email]] once implementation begins.
