# Brainstorm: Resend.com Mail Integration

**Date:** 2026-04-05  
**Status:** Decisions recorded — ready for implementation planning  

---

## 0. Decisions Log

Answers to open questions — agreed before implementation.

| # | Question | Decision |
|---|----------|----------|
| 1 | Sending domain | Configured via `.env` (`MAIL_FROM_ADDRESS`). No Settings UI — DNS setup is a deploy-time concern, not a runtime one. |
| 2 | Webhook secret rotation | `.env` only (`RESEND_WEBHOOK_SECRET`). Storing secrets in the DB (Settings UI) is a security anti-pattern — rotation means a redeploy, which is acceptable. |
| 3 | Log retention | MongoDB TTL index; default **90 days**, configurable via `EMAIL_LOG_TTL_DAYS` env var. |
| 4 | Unsubscribe UX | **Yes** — one-click unsubscribe footer link required. Implement `GET /unsubscribe/{token}` route with HMAC-signed tokens per recipient. |
| 5 | Multi-environment | **Separate API keys per env** (`RESEND_KEY` differs in `.env.staging`, `.env.production`). Keeps analytics clean and avoids cross-env noise. Resend's test mode is not used — real delivery in all envs, different projects in Resend dashboard. |
| 6 | Race condition | `updateOrCreate` on `resend_id` + webhook handler dispatched as a **queued job** (`ProcessResendWebhook`). Eliminates the race and keeps the webhook response under 200ms. |
| 7 | Open tracking | **Both** — enable Resend's open pixel but treat open rate as a lower-bound estimate (pixel blockers). Supplement with click-through rate as the more reliable engagement signal. Weight CTR higher in AI context. |
| 8 | Scope | **Spec-first** — finalize the MD file before writing any code. Implementation will follow separately. |

---

## 1. Why Resend?

The project currently uses `MAIL_MAILER=log` — a placeholder with no real delivery. Resend is a developer-first transactional email API with:

- A Laravel-native SDK (`resend/resend-laravel`) that registers as a standard `Mail` driver — zero disruption to existing `Mail::send()` / `Notification` calls.
- Signed webhooks for delivery events (delivered, opened, bounced, complained, clicked).
- A clean dashboard + per-email analytics.
- Generous free tier (3 000 emails/month).

---

## 2. Goals

| Goal | Success Criteria |
|------|-----------------|
| Replace the `log` mailer with Resend | Emails sent in production land in inboxes |
| Track delivery lifecycle per email | Each sent email has a status record in MongoDB |
| Surface status in the admin UI | Admins can see sent/bounced/opened counts |
| Feed delivery data to AI features | AI can adjust content strategy based on engagement |

---

## 3. Integration Architecture

### 3.1 Mail Driver Swap

```
composer require resend/resend-laravel
```

`.env` changes (all config lives here — no Settings UI for credentials):

```env
MAIL_MAILER=resend
RESEND_KEY=re_xxxxxxxxxxxx            # different value per environment
RESEND_WEBHOOK_SECRET=whsec_xxxxxxxx  # never stored in DB
MAIL_FROM_ADDRESS=no-reply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
EMAIL_LOG_TTL_DAYS=90                 # MongoDB TTL index default
```

`config/mail.php` — add under `mailers`:

```php
'resend' => [
    'transport' => 'resend',
],
```

No changes needed to existing `Mail::` calls or `Notification` classes — Laravel's mailer abstraction handles this transparently.

---

### 3.2 Email Dispatch Tracking (outbound)

Every sent email needs a record before the webhook arrives. Introduce a lightweight MongoDB collection `email_logs`:

**Model:** `app/Models/EmailLog.php`

```
email_logs collection
─────────────────────
_id             ObjectId
resend_id       string        ← from Resend API response headers
recipient       string        (email address)
subject         string
mailable        string        (class name, e.g. App\Mail\WelcomeMail)
context_type    string?       (morphable: 'user', 'page', etc.)
context_id      string?
status          string        enum: queued | sent | delivered | opened | bounced | complained | clicked
opened_at       datetime?
delivered_at    datetime?
bounced_at      datetime?
clicked_at      datetime?
metadata        object        (arbitrary key/value for AI context)
created_at      datetime
updated_at      datetime
```

**Capture the Resend message ID** — use a `MessageSent` listener:

```php
// app/Listeners/LogSentEmail.php
Event::listen(MessageSent::class, function (MessageSent $event) {
    $resendId = $event->message->getHeaders()->get('X-Resend-ID')?->getValue();
    EmailLog::create([
        'resend_id' => $resendId,
        'recipient' => ...,
        'subject'   => ...,
        'status'    => 'sent',
    ]);
});
```

---

### 3.3 Resend Webhook Handler

Resend POSTs signed JSON to a URL you register in their dashboard.

**Route** (unauthenticated, webhook-signed):

```php
// routes/web.php  (or routes/api.php)
Route::post('/webhooks/resend', ResendWebhookController::class)
     ->name('webhooks.resend')
     ->middleware('resend.webhook'); // signature verification
```

**Signature Verification Middleware** — `app/Http/Middleware/VerifyResendWebhook.php`:

```php
// Resend sends: svix-id, svix-timestamp, svix-signature headers
// Verify with the webhook secret from RESEND_WEBHOOK_SECRET
```

Use the official `standardwebhooks/standardwebhooks` PHP library (Resend uses Svix/Standard Webhooks under the hood). Secret read from `RESEND_WEBHOOK_SECRET` env var only — never from DB.

**Controller** — `app/Http/Controllers/ResendWebhookController.php`:

The controller immediately dispatches a queued job (`ProcessResendWebhook`) and returns `204` — keeps response time under 200ms and eliminates the write-before-webhook race condition.

```php
public function __invoke(Request $request): Response
{
    ProcessResendWebhook::dispatch($request->all());
    return response()->noContent();
}
```

**Job** — `app/Jobs/ProcessResendWebhook.php`:

Uses `updateOrCreate` on `resend_id` so late-arriving webhooks (before `email_logs` record exists) self-heal:

```php
$statusMap = [
    'email.sent'       => 'sent',
    'email.delivered'  => 'delivered',
    'email.opened'     => 'opened',
    'email.clicked'    => 'clicked',
    'email.bounced'    => 'bounced',
    'email.complained' => 'complained',
];

if ($status = $statusMap[$type] ?? null) {
    EmailLog::updateOrCreate(
        ['resend_id' => $data['email_id']],
        ['status' => $status, "{$status}_at" => now()]
    );
}
```

---

### 3.4 Bounce / Complaint Handling

Bounced and complained addresses should be suppressed automatically:

- **Hard bounce** → mark user's email as `email_verified = false`, flag `bounce_hard = true`, add to `suppressed_emails` collection, stop sending.
- **Complaint (spam)** → add to `suppressed_emails`, log to `ActivityLog`, stop all marketing emails to that address.
- `SuppressedEmail` collection acts as a pre-flight blocklist — `LogSentEmail` listener checks it before writing the outbound record, skipping suppressed addresses entirely.

---

### 3.5 Unsubscribe Flow

Required for Gmail/Yahoo bulk sender compliance.

**Route** (public, no auth):

```php
Route::get('/unsubscribe/{token}', UnsubscribeController::class)->name('unsubscribe');
```

**Token** — HMAC-SHA256 signed, contains `email` + `expiry`. Generated at send time, embedded in every email footer as a one-click link. No login required.

**Controller** verifies the HMAC, adds the email to `suppressed_emails` with `reason: 'unsubscribed'`, and renders a simple "You've been unsubscribed" confirmation page (public Inertia page, no layout chrome).

The footer link also sets the RFC 8058 `List-Unsubscribe` mail header — required for one-click unsubscribe in Gmail.

---

### 3.6 Admin UI Surface

Add an **Email Logs** section to the admin panel:

**Route:** `GET /admin/email-logs` → `EmailLogController@index`  
**Permission required:** `email_logs.view` (add to `Role::availablePermissions()`)

**Inertia page:** `resources/js/Pages/EmailLogs/Index.tsx`

Features:
- TanStack Table with columns: Recipient, Subject, Status (badge), Sent At, Delivered/Opened/Bounced At.
- Filters: status, date range, mailable class.
- Summary cards at top: Total Sent, Delivery Rate %, Open Rate %, Bounce Rate %.
- Click a row → drawer/modal with full event timeline.

**Dashboard widget:** Add a small email health card to the existing dashboard (Recharts line chart — sends/day vs deliveries/day for last 30 days).

---

## 4. AI-Native Angles

This is where Resend data becomes interesting beyond plain deliverability:

### 4.1 Content Performance Feedback Loop

`AiContextService` already aggregates context for AI prompts. Extend it to include email engagement:

```php
// In AiContextService
public function getEmailEngagementContext(): array
{
    return EmailLog::where('mailable', WelcomeMail::class)
        ->groupBy('status')
        ->selectRaw('status, count(*) as count')
        ->get()
        ->toArray();
}
```

Feed this into subject-line generation prompts: *"Previous subject lines with >40% open rate used urgency words. Suggest 5 subject lines for this campaign."*

**Signal weighting:** Click-through rate (CTR) is the primary engagement signal — more reliable than open rate since many clients block tracking pixels. Open rate is treated as a lower-bound estimate. AI prompts should weight CTR 2× vs open rate.

### 4.2 Smart Send-Time Optimization

Track `opened_at` hour distribution per user segment → suggest optimal send windows to the AI Hub's campaign scheduler.

### 4.3 Bounce-Aware Page Publishing

When a page is published and a notification email is sent, log the `page_id` in `metadata`. If bounce rate for a page's notification batch is high → surface a warning in the Pages admin ("High bounce rate on last publish notification — check your subscriber list").

### 4.4 AI-Generated Digest Emails

Use `AiService` to generate weekly digest emails from recently published pages. Resend's open/click data feeds back to rank which content types drive engagement, closing the loop for the next digest.

---

## 5. Packages & Dependencies

| Package | Purpose |
|---------|---------|
| `resend/resend-laravel` | Official Laravel mail driver |
| `standardwebhooks/standardwebhooks` | Svix/Standard Webhooks signature verification |

Both are composer packages — no frontend dependencies.

---

## 6. Implementation Phases

### Phase 1 — Mail Driver (1–2h)
- [ ] Install `resend/resend-laravel`
- [ ] Configure `.env` + `config/mail.php`
- [ ] Smoke-test email verification flow

### Phase 2 — Outbound Logging (2–3h)
- [ ] Create `EmailLog` model + migration (MongoDB collection)
- [ ] `LogSentEmail` listener on `MessageSent`
- [ ] Capture `resend_id` from response headers

### Phase 3 — Webhook Handler (2–3h)
- [ ] `VerifyResendWebhook` middleware (Standard Webhooks sig check, reads `RESEND_WEBHOOK_SECRET`)
- [ ] `ResendWebhookController` → dispatches `ProcessResendWebhook` job (immediate 204 response)
- [ ] `ProcessResendWebhook` job — `updateOrCreate` status updates
- [ ] Register webhook URL in Resend dashboard

### Phase 4 — Suppression + Unsubscribe (2–3h)
- [ ] `SuppressedEmail` model + `suppressed_emails` collection
- [ ] Hard bounce → flag user (`bounce_hard`, `email_verified = false`), add to suppressions
- [ ] Complaint → add to suppressions + `ActivityLog` entry
- [ ] `GET /unsubscribe/{token}` — HMAC-signed token verification, suppression insert, confirmation page
- [ ] `List-Unsubscribe` header injected on all outbound mail
- [ ] MongoDB TTL index on `email_logs.created_at` (reads `EMAIL_LOG_TTL_DAYS`)

### Phase 5 — Admin UI (3–4h)
- [ ] `EmailLogController` + Inertia page
- [ ] TanStack Table with filters
- [ ] Dashboard widget (Recharts)

### Phase 6 — AI Integration (open-ended)
- [ ] Extend `AiContextService` with engagement context
- [ ] Subject-line suggestion feature in AI Hub
- [ ] Digest email generator

---

## 7. Resolved Decisions (summary)

All open questions answered — see §0 for full rationale.

- Credentials (`RESEND_KEY`, `RESEND_WEBHOOK_SECRET`) live in `.env` only, never in the DB.
- Log TTL: 90 days via MongoDB TTL index, overridable with `EMAIL_LOG_TTL_DAYS`.
- Unsubscribe flow is in scope — HMAC-signed tokens, `List-Unsubscribe` header.
- Separate Resend API keys per environment; no shared test mode.
- Webhook handler queued (`ProcessResendWebhook` job) with `updateOrCreate` for race safety.
- CTR is the primary AI engagement signal; open rate is secondary/indicative.

---

## 8. Risks & Mitigations

| Risk | Mitigation |
|------|-----------|
| Webhook arrives before `email_logs` record (race) | Use `updateOrCreate` with `resend_id`; queue the webhook handler |
| Resend API key exposed in logs | Ensure `RESEND_KEY` is in `.env` only, never logged |
| Webhook endpoint is a DDoS target | Rate-limit the webhook route; Svix signature check rejects invalid requests fast |
| Open-tracking pixel blocked by email clients | Treat open rate as a floor, not exact; supplement with click-through rate |
