# Stack

> Technology choices and key design decisions.

Last updated: 2026-04-05

## Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 13, PHP |
| Database | MongoDB (`mongodb/laravel-mongodb`) |
| Frontend | React 18 + TypeScript, Inertia.js |
| Styling | Tailwind CSS, Shadcn/Radix UI |
| Build | Vite 7 |
| Auth | Laravel Sanctum + Sessions |

## Key design decisions

**Service layer over fat controllers.** All business logic lives in `app/Services/`.
Controllers are thin orchestrators. This makes services testable in isolation and
keeps controllers readable.

**Typed pipeline for Vault uploads.** Rather than a monolithic upload handler, uploads
pass through a sequence of discrete pipe classes. Each pipe does one thing and passes
a typed DTO to the next. Easy to add, remove, or reorder stages. See [[modules/vault]].

**MongoDB for everything.** No relational SQL in production. The `mongodb/laravel-mongodb`
package provides Eloquent-compatible models. Tests override to SQLite in-memory.

**AI config at runtime.** AI provider keys and settings are stored in the database and
managed via the admin UI, not in `.env` or config files. See [[modules/ai-hub]].

**Custom permission middleware.** The `can` middleware alias points to `CheckPermission`,
not Laravel's built-in gate. Permissions are cached per user. See [[modules/permissions]].

**Dual content format.** Public routes respond with HTML normally and with
Markdown+YAML frontmatter when `Accept: text/markdown` is sent. This is the
"AI-native" aspect of the CMS.

## See also

- [[architecture/request-flow]] — how a request moves through the system
- [[architecture/middleware]] — full middleware stack
- [[modules/services]] — service layer details
- [[frontend/ui-stack]] — React/Inertia frontend
- [[database/collections]] — MongoDB model conventions
