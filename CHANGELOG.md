# Changelog

All notable changes to Untitled CMS will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

---

## [0.5.0] — 2026-09-13

### Added
- **Windows Installer** — Native PowerShell installer (`install.ps1`) with prerequisite checks, mirroring `install.sh`.
- **AI Chat UI Primitives** — Chat sidebar rebuilt on shadcn chat components (`bubble`, `message`, `message-scroller`, `marker`).
- **Vault Form Requests** — Dedicated request classes for move, batch move, batch restore/delete, rename, alt text, and optimization toggles; batch `uuids` capped at 500.
- **Vault Folder Unique Index** — Migration adding a unique index on `vault_folders` (idempotent; a lost write race returns 422).
- **Test Coverage** — Feature tests for menus, vault folders/uploads, policies, AI chat, banners, and public Markdown pages; unit test for `AiHttpClient`.
- **`AGENTS.md`** — Canonical configuration for all AI coding agents; `CLAUDE.md` and `GEMINI.md` now forward to it.

### Changed
- **AI Provider HTTP** — Provider calls now go through `AiHttpClient` (timeouts + logging); `SafeHttpClient` remains for untrusted URLs.
- **AI Chat Resilience** — `AiService` chat retries once on provider rate limits.
- **Vault Browser** — `Vault/Index.tsx` split into `useVaultBrowser` hook, `VaultDialogs`, and `VaultFolderInfoPopover`; search is debounced and stale responses are dropped.
- **Installer Hardening** — `install.sh` fails fast with an error trap, warns on Windows, and stops if `composer install` fails.
- **Documentation** — README facts corrected against the code; wiki cross-references converted to standard Markdown links; legacy `RELEASE_NOTES_*` files removed.
- **Package Metadata** — `composer.json` gains Packagist keywords, homepage, and support links; the pinned `version` field was removed so Packagist derives versions from git tags.

### Fixed
- **Menus** — Item validation matches the real `{id, title, url, target, order, subItems}` shape; saves no longer strip item data.
- **Vault Policies** — Folder creation requires `media.create` even with a parent; new `forceDelete` requires `media.delete`; AI image saves authorize the target folder; folder restore checks for name collisions.
- **Vault Folder Listing** — `folders.list?all=1` returns the full tree, so nested folders are visible.
- **Draft Preview** — Public draft preview is authorized through `PagePolicy::viewAny`.
- **Banners** — Slug uniqueness uses `Rule::unique(...)->ignore($id)` so edits don't collide with themselves.
- **AI Context** — Recent pages/banners sorted by `created_at` explicitly, fixing context ordering on MongoDB.

### Security
- **Dependencies** — Updated vulnerable Composer dependencies.

---

## [0.4.0] — 2026-06-13

### Added
- **Apple Password Rules** — Added Apple `passwordrules` hint to improve the password generation experience.
- **Documentation Updates** — Promoted Shadcn UI theming by adding a comprehensive CLI preset command guide to the README.

### Changed
- **Laravel 13 Upgrade** — Integrated support for Laravel 13.8 and 13.14 features.
- **Security & Performance** — Conducted a comprehensive security sweep across the platform and applied significant performance optimizations within the Media Vault.

---

## [0.3.0] — 2026-05-23

### Changed
- **React 19 & Tailwind v4** — Upgraded frontend to React 19 and Tailwind v4, and applied the `b2fA` preset.
- **Laravel 13 Skeleton** — Synchronized Laravel 13 skeleton and hardened backend security (including a patch for CVE-2026-44167 in phpseclib).
- **Media Vault Optimizations** — Hardened the Media Vault and applied performance and memory optimizations.
- **Dependencies** — Cleaned up unused dependencies to unblock Dependabot.

---

## [0.2.0] — 2026-04-12

### Added
- **OpenRouter Integration** — Native support for OpenRouter in the AI Hub for text and vision generation tasks.
- **AI Hub Security Refactor** — Implemented explicit API key revocation UI and optimized `AiContextService`.
- **LLM Wiki** — Established a persistent, agent-maintained knowledge base (`wiki/`) enforcing Automated Retrieval Protocols for AI assistants.

### Changed
- **Laravel 13 Upgrade** — Migrated framework from Laravel 12 to 13.4, bumping MongoDB, Laravel AI, and dependencies. Replaced deprecated HTML Purifier wrapper with native service.
- **Email Webhook Abstraction** — Unified webhook handling across Resend, Mailgun, and SendGrid to a generic `/webhooks/email` endpoint.

---

## [0.1.0] — 2026-03-15

Initial public release.

### Added

- `/llms.txt` and `/llms-full.txt` endpoints — AI-discoverability standard (llmstxt.org), exposing all published content as plain Markdown for LLM ingestion and RAG pipelines
- GitHub Actions CI workflow — automated testing (PHP 8.2 + 8.3), Pint code style, security audit, and frontend build on every push and pull request
- GitHub issue templates (bug report, feature request) and pull request template
- **Auth & RBAC** — Login, registration, email verification, password reset, token-based user invitations, granular role/permission system with Laravel Gate policies
- **Social Login** — OAuth via Google and GitHub (Laravel Socialite)
- **Pages** — CKEditor 5 rich text editor, Draft/Published workflow, SEO meta fields, AI-generated meta, dynamic public routing, scheduled publishing support
- **Banners** — Drag-and-drop reordering (`@dnd-kit`), active/inactive scheduling with `start_at / end_at`
- **The Vault** — Hierarchical media manager with 3-panel resizable layout, secure 7-stage upload pipeline (double-extension detection → MIME validation → image sanitization → moderation → ClamAV scan → UUID generation → metadata extraction), folder-level permissions, full audit log, AI-generated alt text
- **AI Hub** — Multi-provider manager supporting OpenAI, Anthropic, Gemini, Groq, Mistral, Deepseek, and Ollama; runtime configuration (no restart required), per-hub monthly usage tracking, text generation, SEO meta generation, vision-based alt text, image generation
- **Markdown for Agents** — Public pages respond with YAML frontmatter + Markdown when `Accept: text/markdown` is sent; `Content-Signal` and `x-markdown-tokens` headers included
- **Sitemap for Agents** — `/sitemap.md` optimised for AI crawlers
- **RSS Feed** — `/rss` and `/feed`
- **Dashboard** — Analytics cards and Recharts charts, recent activity feed
- **Activity Log** — Comprehensive, filterable audit trail for all admin actions with before/after state snapshots
- **Settings** — Admin-configurable key/value store, custom maintenance mode with admin bypass, custom error pages
- **Menus** — Navigation system with drag-and-drop hierarchy management
- **Profile** — User profile editing, password change, account deletion
- **Security** — OWASP Top 10 mitigations: SSRF protection (`SafeHttpClient`), XSS blocking in banner URLs, input sanitization, admin role protection, image polyglot prevention
- **Installation** — Interactive `install.sh` installer and `composer run setup` one-command setup
- **Docker** — Development and production `docker-compose` configurations
- **Deployment** — `deploy.sh` and `backup.sh` scripts with Nginx + systemd examples in `docs/deployment.md`
- **Dark mode** — System-preference aware, toggle in admin UI
- **34 permissions** — Organised by resource group across all modules

[Unreleased]: https://github.com/watchtower/untitled-cms/compare/0.5.0...HEAD
[0.5.0]: https://github.com/watchtower/untitled-cms/compare/0.4.0...0.5.0
[0.4.0]: https://github.com/watchtower/untitled-cms/compare/0.3.0...0.4.0
[0.3.0]: https://github.com/watchtower/untitled-cms/compare/0.2.0...0.3.0
[0.2.0]: https://github.com/watchtower/untitled-cms/compare/0.1.0...0.2.0
[0.1.0]: https://github.com/watchtower/untitled-cms/releases/tag/0.1.0
