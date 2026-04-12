# Email Provider Abstraction — Architecture Reference

The application uses a provider-agnostic layer for email handling, allowing seamless switching between **Resend**, **Mailgun**, and **SendGrid** via environment variables.

---

## Architecture Overview

All email provider logic is abstracted through the `WebhookProvider` contract. This ensures that the core application logic (logging, suppression, webhooks) remains untouched when switching mail services.

```
MAIL_WEBHOOK_PROVIDER=resend   ← central switch

app/Services/EmailWebhooks/
  Contracts/
    WebhookProvider.php         ← core interface
  ResendWebhookProvider.php     ← Svix signature logic
  MailgunWebhookProvider.php    ← HMAC-SHA256 logic
  SendGridWebhookProvider.php   ← ECDSA public-key verification
```

---

## Core Components

### 1. Webhook Infrastructure
The system uses generic routes and middleware:
- **Route**: `POST /webhooks/email`
- **Middleware**: `VerifyEmailWebhook` (injects active provider to verify signatures)
- **Controller**: `EmailWebhookController` (dispatches processing job)
- **Job**: `ProcessEmailWebhook` (normalizes events and updates logs/suppressions)

### 2. Database Schema
Email logs use a provider-neutral field for tracking:
- **Field**: `provider_message_id` (formerly `resend_id`)
- **Collection**: `email_logs` (MongoDB)
- **Indexes**: Unique index on `provider_message_id` for $O(1)$ lookups.

### 3. Outbound Logging
The `LogSentEmail` listener uses the `WebhookProvider` interface to resolve the unique message ID from provider-specific headers (e.g., `X-Resend-ID` or `X-Mailgun-Message-Id`) before saving to the database.

---

## Configuration (`.env`)

```dotenv
# ─── Email Provider Configuration ─────────────────────────────────────────────
MAIL_WEBHOOK_PROVIDER=resend        # options: resend | mailgun | sendgrid

# Resend (default)
RESEND_KEY=re_xxxxxxxxxxxx
RESEND_WEBHOOK_SECRET=whsec_xxxxxxxx

# Mailgun
# MAILGUN_WEBHOOK_SIGNING_KEY=key-xxxxxxxx

# SendGrid
# SENDGRID_WEBHOOK_PUBLIC_KEY=MFkwEw...
```

---

## Feature Comparison

| Feature | Resend | Mailgun | SendGrid |
|---|---|---|---|
| **Signature** | Svix (V1) | HMAC-SHA256 | ECDSA (SHA256) |
| **Message ID** | `X-Resend-ID` | `X-Mailgun-Message-Id` | `X-Message-Id` |
| **Normalization** | `data.email_id` | `event-data.id` | `sg_message_id` |

---

## Implementation History
Refactored [2026-04-06] to support multi-provider scaling. Legacy `resend_id` fields were migrated using a collection-wide `$rename` operation to preserve historical data.
