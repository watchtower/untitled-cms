# Services

> Overview of app/Services/* and how they relate to each other.

Last updated: 2026-04-05

## Overview

All business logic lives in `app/Services/`. Controllers call services; services
call models. Services should not call other services unless clearly necessary —
keep the dependency graph shallow.

## Service inventory

### AiService
Multi-provider AI orchestration. Supports OpenAI, Gemini, and Stability AI.
Providers are configured at runtime via the admin UI (not hardcoded). Handles
text generation and image generation. Rate-limited at the route level (30/min
text, 10/min image). See [[modules/ai-hub]] for provider config details.

### VaultService
Media management. Handles file uploads through the security pipeline and stores
metadata. Entry point for all vault operations (upload, delete, move, folder
management). Internally delegates to the pipe sequence in `app/Vault/Pipes/`.
See [[modules/vault]] for the full pipeline.

### AiContextService
Aggregates context for AI requests — collects relevant DB data (pages, settings,
etc.) and caches the result to avoid redundant queries within a session. Used by
`AiService` to build prompts with project context.

### SettingsService
Key/value settings store with cache layer. Wraps the `settings` collection.
Use this instead of reading settings directly from the DB — it handles caching
transparently.

### SafeHttpClient
SSRF-protected HTTP client. **Use this for all outbound HTTP requests**, not
Laravel's `Http` facade directly. It validates URLs against an allowlist/blocklist
to prevent server-side request forgery. If you need to call an external API,
route it through `SafeHttpClient`.

## When to add a new service

Add a service when:
- Logic is reused across multiple controllers
- Logic is complex enough to warrant unit testing in isolation
- Logic has external side effects (file I/O, HTTP calls, cache writes)

Don't add a service for simple one-off CRUD — a controller method is fine.

## See also

- [[modules/vault]] — VaultService pipeline detail
- [[modules/ai-hub]] — AI provider configuration
- [[architecture/stack]] — how services fit in the request flow
