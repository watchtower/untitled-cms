# Changelog

All notable changes to Untitled CMS will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

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

[Unreleased]: https://github.com/watchtower/untitled-cms/compare/v0.4.0...HEAD
[0.4.0]: https://github.com/watchtower/untitled-cms/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/watchtower/untitled-cms/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/watchtower/untitled-cms/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/watchtower/untitled-cms/releases/tag/v0.1.0
