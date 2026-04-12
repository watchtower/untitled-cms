Untitled CMS v0.2.0 brings official support for Laravel 13 alongside major upgrades to the AI Hub and email processing layer.

---

### Highlights

**Laravel 13 Upgrade** — The core framework has been migrated to Laravel 13.4, keeping the CMS on the absolute cutting-edge. MongoDB, Laravel Sanctum, and provider SDKs have all been bumped to support the latest specifications. As part of this, the deprecated `mews/purifier` has been replaced with a native `HtmlSanitizer` service that preserves 100% backwards compatibility in functionality.

**OpenRouter Integration** — OpenRouter has joined OpenAI, Gemini, and Anthropic in the AI Hub. You can now route text and vision requests through OpenRouter's massive model catalog directly from the admin dashboard. API Key management has also been hardened across the board with explicit revocation toggles.

**Multi-provider Email Webhooks** — Webhook processing has been completely abstracted. Whether you're using Resend (Svix signatures), Mailgun (HMAC verification), or SendGrid (ECDSA signing), all traffic now gracefully runs through a single decoupled `/webhooks/email` endpoint, dramatically simplifying email bounce and suppression tracking.

**LLM Wiki** — We've pioneered an LLM-maintained local knowledge base inside the `wiki/` directory. Agent configurations now natively utilize "Automated Retrieval Protocols", meaning any future AI you use to develop upon the CMS will automatically fetch context, architecture guides, and schema definitions before acting.

---

### What's Changed

- Add `OpenRouter` native text and vision generation support.
- Add `clear_key` mechanisms to AI Hub controller and React UI.
- Upgrade to `laravel/framework ^13.0`.
- Upgrade `mongodb/laravel-mongodb ^5.7`.
- Implement `WebhookProvider` contracts for Resend, Mailgun, and SendGrid.
- Establish `wiki/` Agent Knowledge Base protocols.

---

### Updating

1. Pull the latest repository changes.
2. Run `composer install` and `npm install` to load the updated Laravel 13 dependencies.
3. Run `npm run build` to re-compile your front-end assets.
4. Run `php artisan migrate` to push any new database indexes.
