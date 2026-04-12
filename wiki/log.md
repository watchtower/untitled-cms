# Wiki Log

Append-only record of wiki operations. Format: `## [YYYY-MM-DD] <op> | <title>`

---

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
