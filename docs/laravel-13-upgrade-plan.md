# Laravel 13 Upgrade Report & Implementation Plan

## 1. Overview

This document outlines the strategy, risks, and step-by-step implementation plan for upgrading Untitled CMS from Laravel 12 to Laravel 13. The goal is a smooth transition with minimal downtime, improved performance, and long-term maintainability. Since this is a one-version hop (12 → 13), a direct upgrade is appropriate.

---

## 2. Objectives

- Upgrade from `laravel/framework ^12.0` to `^13.0`
- Maintain compatibility with MongoDB, Inertia.js, and the AI service layer
- Adopt any new Laravel 13 conventions where applicable
- Keep the test suite green throughout

---

## 3. Current System Assessment

### 3.1 Framework & Environment

| Item | Value |
|---|---|
| Laravel Version | 12.x |
| PHP Version | 8.4 |
| Database | MongoDB (production), SQLite in-memory (tests) |
| Driver | `mongodb/laravel-mongodb ^5.5` |
| Frontend | React 18 + TypeScript, Inertia.js, Vite 7 |
| Auth | Sanctum 4.0 + Socialite 5.24 |
| Queue | Laravel Queue (sync/database) |

**Key production dependencies:**

```
laravel/framework          ^12.0
laravel/sanctum            ^4.0
laravel/socialite          ^5.24
laravel/ai                 ^0.2.1
inertiajs/inertia-laravel  ^2.0
mongodb/laravel-mongodb    ^5.5
tightenco/ziggy            ^2.0
guzzlehttp/guzzle          ^7.10
intervention/image         ^3.11
mews/purifier              ^3.4
league/html-to-markdown    ^5.1
resend/resend-laravel      *
```

### 3.2 Risk Areas

| Area | Risk Level | Notes |
|---|---|---|
| `mongodb/laravel-mongodb` | **High** | Must verify L13 support in `^5.x` or bump to `^6.x`; all models override `$connection` and `$collection` |
| `laravel/ai ^0.2.1` | **High** | First-party package; may lag the framework release |
| `inertiajs/inertia-laravel ^2.0` | **Medium** | Track whether `^2.x` supports L13 or needs `^3.x` |
| Custom middleware order | **Medium** | `HandleInertiaRequests`, `CheckRedirects`, `CheckMaintenanceMode` depend on framework middleware contracts |
| `SafeHttpClient` + SSRF protection | **Low** | Wraps Guzzle 7; no direct framework coupling |
| `resend/resend-laravel: *` | **Medium** | Pinned to `*`, will pull anything — verify L13 compatibility and pin a real constraint |
| `mews/purifier` | **Low** | Third-party; verify L13 support |
| PHPUnit 11 / test suite | **Low** | Already on PHPUnit 11; likely L13-compatible |

---

## 4. Key Changes in Laravel 13 (To Validate Before Starting)

Laravel 13 targets PHP 8.4+ (already met). Verify these against the official upgrade guide:

- Any removed facades or deprecated method aliases from L12
- Changes to `Illuminate\Http\Middleware\HandleCors` or middleware base classes
- Eloquent model casting or serialization changes (affects all MongoDB model `$casts` arrays)
- Service provider `boot()`/`register()` contract changes
- Routing — check if `CheckRedirects` middleware still hooks correctly
- Queue contract changes (affects AI job dispatching)
- `php artisan optimize` behavior changes (cache paths, etc.)

---

## 5. Upgrade Strategy

**Approach:** Direct upgrade (L12 → L13). Only one version hop; incremental is unnecessary.

**Branching:**

```bash
git checkout -b feature/laravel-13-upgrade
```

---

## 6. Implementation Plan

### Phase 1 — Preparation

- [ ] Run `composer outdated` and capture the full output
- [ ] Pin `resend/resend-laravel` away from `*` — identify current resolved version and set a real constraint
- [ ] Confirm `mongodb/laravel-mongodb` releases a L13-compatible version (check their GitHub releases)
- [ ] Confirm `laravel/ai` releases a L13-compatible version
- [ ] Run `composer run test` on the current branch and ensure all tests pass before touching anything

### Phase 2 — Environment

- PHP 8.4 is already the minimum for L13 — no PHP bump needed
- Ensure local MongoDB 7.x+ is running
- CI already has a MongoDB service container (added in commit `94fb850`)

### Phase 3 — Dependency Upgrade

Update `composer.json`:

```json
"laravel/framework": "^13.0",
"laravel/sanctum": "^5.0",
"laravel/socialite": "^6.0"
```

> **Note:** Sanctum and Socialite typically bump major versions alongside the framework. Verify the correct constraints on release.

Then:

```bash
composer update laravel/framework --with-all-dependencies
composer update
```

Resolve conflicts package by package. The most likely friction points are `mongodb/laravel-mongodb` and `laravel/ai`.

### Phase 4 — Codebase Refactor

Focus areas specific to this project:

- All custom middleware in `app/Http/Middleware/` — verify constructor signatures against new framework contracts
- `HandleInertiaRequests::share()` — check if the `$request` parameter signature changed
- `ActivityLogger::log()` — verify `Illuminate\Support\Facades\Log` API is unchanged
- `SafeHttpClient` — check if any Guzzle integration points changed in `Http` facade internals
- `AiService` — if `laravel/ai` bumps major, review API surface changes carefully
- All MongoDB models: test `$casts`, `$dates`, relationship methods after upgrade
- Review any usage of `app()->version()` checks or conditional logic keyed to the framework version

### Phase 5 — Testing

```bash
composer run test
```

Manual QA checklist:

- [ ] Login + OAuth flows (Google, GitHub) via Socialite
- [ ] Admin dashboard loads, Inertia props hydrate correctly
- [ ] Vault upload pipeline (all 6 pipes)
- [ ] AI Hub: text generation (OpenAI / Gemini), image generation, chat
- [ ] Page publish/unpublish, `Accept: text/markdown` response on `/{slug}`
- [ ] `/llms.txt`, `/llms-full.txt`, `/sitemap.md` endpoints
- [ ] Background queue jobs drain correctly
- [ ] Settings cache busting (role save → cache bust)
- [ ] Maintenance mode toggle via SettingsService

### Phase 6 — Performance & Security Review

```bash
php artisan optimize
php artisan route:cache
php artisan view:cache
```

- Verify `CheckRedirects` middleware doesn't regress (it hits MongoDB on every request — confirm indexing is intact)
- Re-run SSRF test cases through `SafeHttpClient`

### Phase 7 — Deployment

- Deploy to staging first; run full QA
- Production deploy during low-traffic window
- After deploy: `php artisan optimize`, `php artisan queue:restart`

---

## 7. Rollback Plan

- The `feature/laravel-13-upgrade` branch is isolated — `master` remains on L12
- MongoDB data is schema-less; no migration rollback risk
- If production deploy fails: revert to previous release tag, run `php artisan queue:restart`
- Keep a `composer.lock` snapshot from the L12 state committed before the upgrade begins

---

## 8. Timeline

| Phase | Duration |
|---|---|
| Preparation + dependency audit | 0.5 day |
| Dependency resolution | 0.5–1 day |
| Codebase refactor | 1–2 days |
| Testing + regression fixes | 1–2 days |
| Staging deploy + UAT | 0.5 day |
| Production deploy | 0.5 day |
| **Total** | **4–6 days** |

---

## 9. Success Criteria

- `php artisan --version` reports Laravel 13.x
- `composer run test` passes with zero failures
- All manual QA checklist items pass on staging
- No MongoDB query regressions (`activity_logs`, `vault_files`, `chat_sessions` writes)
- Inertia shared props (`auth`, `settings`, `menus`) hydrate correctly on all pages

---

## 10. Appendix

### Useful Commands

```bash
# Check current versions
php artisan --version
composer show laravel/framework | grep versions

# Upgrade
composer update laravel/framework --with-all-dependencies
composer update

# After upgrade
php artisan migrate --force
composer run test
php artisan optimize

# Diff what changed in vendor contracts
git diff vendor/laravel/framework/src/Illuminate/Http/Middleware/
```

### References

- [Laravel Upgrade Guide — 12.x → 13.x](https://laravel.com/docs/13.x/upgrade)
- [mongodb/laravel-mongodb releases](https://github.com/mongodb/laravel-mongodb/releases)
- [inertiajs/inertia-laravel releases](https://github.com/inertiajs/inertia-laravel/releases)
- [laravel/ai releases](https://github.com/laravel/ai/releases)
