# Middleware

> Web middleware stack and what each layer does.

Last updated: 2026-04-05

## Web middleware stack (in order)

1. **`HandleInertiaRequests`** — shares `auth`, `permissions`, `menus`, `settings`
   as Inertia props on every page load. If a shared prop is missing on the frontend,
   this is the first place to look.

2. **`AddLinkHeadersForPreloadedAssets`** — adds `Link: <url>; rel=preload` headers
   for critical assets. Performance optimization.

3. **`CheckRedirects`** — database-driven URL redirects. Looks up the request path
   in the `redirects` collection and issues a redirect if found. Runs early so
   redirects take effect before page logic.

4. **`CheckMaintenanceMode`** — custom maintenance mode. Serves a maintenance page
   to regular users but lets admins through. This is **not** Laravel's built-in
   maintenance mode — it's a custom implementation that checks the `settings`
   collection and the current user's role.

## Auth middleware

Routes requiring authentication use:
- `auth` — standard Sanctum/session auth check
- `verified` — email verification check (applied to all admin routes)

## Permission middleware

The `can` middleware alias maps to `CheckPermission` (custom), not Laravel's built-in.
Usage: `->middleware('can:resource.action')`. See [[modules/permissions]].

## Webhook middleware

`resend.webhook` alias → `VerifyResendWebhook`. Applied only to `POST /webhooks/resend`.
Verifies Svix/Standard Webhooks v1 HMAC-SHA256 signatures and rejects requests whose
timestamp deviates more than 5 minutes in either direction. CSRF is exempted for this
route in `bootstrap/app.php`. See [[modules/email]] for full detail.

## Event discovery

Auto-discovery is **disabled** via `->withEvents(discover: false)` in `bootstrap/app.php`.
All listeners are registered manually in `AppServiceProvider`. This is intentional —
the mail listener chain (`StopSuppressedEmail` → `InjectUnsubscribeHeaders`) requires a
guaranteed execution order that auto-discovery cannot provide. If you add a new listener,
register it explicitly there.

## Rate limiting

AI endpoints are rate-limited at the route level, not via middleware:
- Text generation: 30 requests/minute
- Image generation: 10 requests/minute

## Gotchas

- `CheckMaintenanceMode` reads from `settings` — if `SettingsService` cache is
  warm with stale data, maintenance mode changes may not take effect immediately.
- `CheckRedirects` runs on every request — keep the `redirects` collection indexed
  on the path field for performance.

## See also

- [[modules/permissions]] — CheckPermission middleware detail
- [[architecture/request-flow]] — where middleware fits in the full request flow
- [[frontend/ui-stack]] — HandleInertiaRequests and shared props
