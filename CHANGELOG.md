# Changelog

All notable changes to Untitled CMS will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

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

[Unreleased]: https://github.com/your-org/untitled-cms/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/your-org/untitled-cms/releases/tag/v0.1.0
