# Email

> Resend-powered outbound email with delivery tracking, suppression, and RFC 8058 unsubscribe support.

Last updated: 2026-04-05

## Overview

All outbound email is sent via [Resend](https://resend.com). Three event listeners hook into
Laravel's mail pipeline to intercept every send, inject unsubscribe headers, and log the result.
Incoming delivery events arrive as signed webhooks and are processed off the queue.

## Event listener pipeline

Listeners are registered manually in `AppServiceProvider` — **auto-discovery is disabled**
(`->withEvents(discover: false)` in `bootstrap/app.php`) to preserve execution order.
Registration order matters: `StopSuppressedEmail` must fire before `InjectUnsubscribeHeaders`
so that propagation halts before headers are injected for a blocked send.

```
MessageSending  →  1. StopSuppressedEmail       — returns false to halt send for suppressed addresses
                   2. InjectUnsubscribeHeaders   — stamps List-Unsubscribe + X-Unsubscribe-Token headers
MessageSent     →  1. LogSentEmail              — creates EmailLog record; reads token from X-Unsubscribe-Token
```

### StopSuppressedEmail (`app/Listeners/StopSuppressedEmail.php`)

Fires on `MessageSending`. Queries `suppressed_emails` by recipient address (indexed). Returns
`false` to cancel the send if the address is suppressed. Logs a warning if no recipients are found.

### InjectUnsubscribeHeaders (`app/Listeners/InjectUnsubscribeHeaders.php`)

Fires on `MessageSending` (after `StopSuppressedEmail`). Generates an AES-256-CBC encrypted
token (`email|expiry`, 30-day window) and stamps three headers onto the outgoing message:
- `List-Unsubscribe: <url>` — RFC 8058 one-click URL
- `List-Unsubscribe-Post: List-Unsubscribe=One-Click` — RFC 8058 POST indicator
- `X-Unsubscribe-Token: <token>` — passes the token to `LogSentEmail` for DB persistence

### LogSentEmail (`app/Listeners/LogSentEmail.php`)

Fires on `MessageSent`. Creates an `EmailLog` record with `resend_id`, recipient, subject,
mailable class, and the unsubscribe token from `X-Unsubscribe-Token`. Fails silently — a
logging failure must never block the user flow.

## Webhook processing

### VerifyResendWebhook middleware (`app/Http/Middleware/VerifyResendWebhook.php`)

Registered as the `resend.webhook` alias. Applied to `POST /webhooks/resend` (CSRF-exempt).
Verifies incoming requests using Standard Webhooks v1 (Svix) HMAC-SHA256:
- Rejects requests where `abs(now - svix-timestamp) > 300` seconds (replay protection in both directions)
- Handles the `whsec_` prefix on the base64-encoded secret
- Supports multiple signatures in `svix-signature` header
- Secret read from `config('services.resend.webhook_secret')` — never `env()` directly

### ProcessResendWebhook job (`app/Jobs/ProcessResendWebhook.php`)

Queued. Maps Resend event types to status strings and updates `email_logs` via
`updateOrCreate(['resend_id' => $emailId], ...)`. On bounce or complaint, also writes to
`suppressed_emails` and flags the user's `bounce_hard` field.

**Gotcha:** `ActivityLogger::log()` third argument must be a model object or `null` — never an
array. Passing an array causes a PHP 8 `TypeError` (not caught by the inner `try/catch`) which
fails the job and triggers retries.

## Suppression

`SuppressedEmail` model (`suppressed_emails` collection) stores addresses that must not receive
email. Reason values: `bounced_hard`, `complained`, `unsubscribed`.

`SuppressedEmail::isSuppressed(string $email)` is the canonical check — normalises to lowercase,
uses the unique index on `email` for an O(1) lookup.

Hard bounces set `User::bounce_hard = true` but **do not** clear `email_verified_at`. An admin
must decide whether to revoke backend access — automated bounce handling must not lock users out.

## Unsubscribe flow

`GET /unsubscribe/{token}` → `UnsubscribeController`. Token is decrypted with `Crypt::decryptString`;
format is `email|expiry_timestamp`. Expired or tampered tokens return 403. Valid tokens create
a `suppressed_emails` record with `reason = 'unsubscribed'` and render `Public/Unsubscribed`.

## Email stats

`EmailLogController` aggregates delivery/open/bounce rates via a single MongoDB `$facet` pipeline.
Stats are cached for 5 minutes under the key `email_log_stats`. The page is gated by the
`email_logs.view` permission via both route middleware (`can:email_logs.view`) and
`Gate::authorize('viewAny', EmailLog::class)` in the controller — `EmailLogPolicy` must be
registered in `AppServiceProvider`.

## Collections

| Collection | Key fields | Indexes |
|---|---|---|
| `email_logs` | `resend_id`, `recipient`, `subject`, `status`, `*_at` timestamps | unique `resend_id`, `recipient`, `subject`, `status` |
| `suppressed_emails` | `email`, `reason`, `metadata` | unique `email` |

## Environment variables

| Variable | Purpose |
|---|---|
| `RESEND_API_KEY` | Resend sending key (`config('services.resend.key')`) |
| `RESEND_WEBHOOK_SECRET` | Svix webhook secret, prefixed `whsec_` (`config('services.resend.webhook_secret')`) |

## See also

- [[database/collections]] — full collection list
- [[architecture/middleware]] — VerifyResendWebhook middleware alias
- [[modules/permissions]] — email_logs.view permission
- [[modules/services]] — ActivityLogger usage pattern
