# Stack

> Technology choices and key design decisions.

Last updated: 2026-07-12

## Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 13, PHP **8.4+** |
| Database | MongoDB (`mongodb/laravel-mongodb`) — see [architecture/mongodb](mongodb.md) |
| Frontend | React 19 + TypeScript, Inertia.js |
| Rich text | TinyMCE 7 (admin pages/banners) |
| Styling | Tailwind CSS v4, Shadcn/Radix UI |
| Build | Vite 7 |
| Auth | Laravel Sanctum + Sessions |

## Key design decisions

**Service layer over fat controllers.** All business logic lives in `app/Services/`.
Controllers are thin orchestrators. This makes services testable in isolation and
keeps controllers readable.

**Typed pipeline for Vault uploads.** Rather than a monolithic upload handler, uploads
pass through a sequence of discrete pipe classes. Each pipe does one thing and passes
a typed DTO to the next. Easy to add, remove, or reorder stages. See [modules/vault](../modules/vault.md).

**MongoDB for everything.** No relational SQL in production. The `mongodb/laravel-mongodb`
package provides Eloquent-compatible models. Tests override to SQLite in-memory.
See [architecture/mongodb](mongodb.md).

**AI config at runtime.** AI provider keys and settings are stored in the database and
managed via the admin UI, not in `.env` or config files. See [modules/ai-hub](../modules/ai-hub.md).

**Two-tier outbound HTTP.** Untrusted URLs use `SafeHttpClient` (SSRF-safe); hub provider
APIs use `AiHttpClient`. See [modules/services](../modules/services.md).

**Custom permission middleware + policy-first controllers.** The `can` middleware alias
points to `CheckPermission`. Controllers should authorize via policies. See [modules/permissions](../modules/permissions.md).

**Dual content format.** Public routes respond with HTML normally and with
Markdown+YAML frontmatter when `Accept: text/markdown` is sent. This is the
"AI-native" aspect of the CMS.

## See also

- [architecture/request-flow](request-flow.md) — how a request moves through the system
- [architecture/middleware](middleware.md) — full middleware stack
- [architecture/mongodb](mongodb.md) — MongoDB decision and test gap
- [modules/services](../modules/services.md) — service layer details
- [frontend/ui-stack](../frontend/ui-stack.md) — React/Inertia frontend
- [database/collections](../database/collections.md) — MongoDB model conventions
