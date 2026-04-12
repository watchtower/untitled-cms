# Laravel 13 Upgrade — Post-Mortem

Completed on `feature/laravel-13-upgrade`. All 53 tests pass. Branch ready for staging.

---

## What Changed

### Framework & direct dependencies

| Package | Before | After | Notes |
|---|---|---|---|
| `laravel/framework` | `^12.0` (v12.54.1) | `^13.0` (v13.4.0) | Core upgrade |
| `laravel/tinker` | `^2.10.1` (v2.11.1) | `^3.0` (v3.0.0) | v2 didn't declare L13 support |
| `laravel/ai` | `^0.2.1` (v0.2.8) | `^0.5` (v0.5.1) | Needed for L13; no API changes required |
| `mongodb/laravel-mongodb` | `^5.5` (v5.6.0) | `^5.7` (v5.7.0) | v5.6 capped at L12 |
| `resend/resend-laravel` | `*` (v0.12 resolved) | `^1.0` (v1.3.2) | Major version jump; wildcard constraint fixed |
| `mews/purifier` | `^3.4` (v3.4.3) | **removed** | No L13-compatible release exists |
| `ezyang/htmlpurifier` | transitive | `^4.19` (v4.19.0) | Promoted to direct dep |

### Codebase changes

**`app/helpers.php`** (new) — global `clean()` helper preserving the `mews/purifier` API:
```php
function clean(string $dirty, string $config = 'default'): string {
    return app(\App\Services\HtmlSanitizer::class)->clean($dirty, $config);
}
```
Registered via `autoload.files` in `composer.json`. Zero changes to the 5 call sites in `PageController` and `AiActionService`.

**`app/Services/HtmlSanitizer.php`** (new) — thin wrapper around `ezyang/htmlpurifier` that reads `config/purifier.php` directly. Existing purifier config untouched.

**`app/Providers/AppServiceProvider.php`** — manually registers the Resend mail transport and client singleton. Required because `resend/resend-laravel` is excluded from auto-discovery (see below).

**`composer.json`** — `resend/resend-laravel` added to `dont-discover`. The v1 package auto-registers `POST /resend/webhook` via its service provider. The app uses its own `POST /webhooks/email` endpoint for all providers, so the built-in route was an unused open endpoint.

**`.env.example`** — added `RESEND_API_KEY` alias. The v1 SDK reads `RESEND_API_KEY` first, falling back to `RESEND_KEY`. Both env vars are documented; existing deployments using `RESEND_KEY` continue to work.

---

## Zero-change areas (verified)

- `AnonymousAgent` constructor and `prompt()` signature — unchanged in `laravel/ai` v0.5
- `Base64Image` — still exists at same namespace
- All 6 custom middleware files — no framework contract changes
- `bootstrap/app.php` — L11-style bootstrapping, fully compatible with L13
- `HandleInertiaRequests::share()` — no signature changes
- `laravel/breeze` (dev) — already declared L13 support in v2.4.1

---

## Deployment checklist

- [ ] Add `RESEND_API_KEY` to staging/production `.env` (same value as `RESEND_KEY`)
- [ ] `composer install --no-dev`
- [ ] `php artisan migrate --force` (no schema changes; safe to run)
- [ ] `php artisan optimize`
- [ ] `php artisan queue:restart`
- [ ] Smoke test: login, page publish, vault upload, AI hub, chat

---

## Blockers hit during upgrade

All resolved without workarounds on the framework itself.

1. **`laravel/tinker` v2.11.1** — declared support up to L12 only. v3.0.0 was available and added.
2. **`mews/purifier` v3.4.3** — no L13 release exists (as of April 2026). Replaced with `HtmlSanitizer` service. Only 5 call sites, all using the default profile.
3. **`laravel/ai` v0.2.8** — needed explicit bump to `^0.5`; Composer wouldn't unlock it automatically.
4. **`mongodb/laravel-mongodb` v5.6.0** — capped at L12. v5.7.0 adds L13 support.
5. **`resend/resend-laravel *`** — resolved to v0.12.0 which caps at L11. Bumped to `^1.0`; wildcard constraint also fixed.
