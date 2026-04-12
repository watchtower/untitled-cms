Untitled CMS is an AI-native content management system built on Laravel 12, MongoDB, and React + Inertia.js. This is the first public release.

---

### Highlights

**AI-first content delivery** — Every public page responds with clean Markdown + YAML frontmatter when requested with `Accept: text/markdown`, making your content natively consumable by AI agents, coding assistants, and RAG pipelines. `/llms.txt` and `/llms-full.txt` expose your full content index for LLM ingestion.

**Multi-provider AI Hub** — Manage OpenAI, Anthropic, Gemini, Groq, Mistral, Deepseek, and Ollama from a single admin UI. Switch providers at runtime, track monthly usage per hub, and use AI for text generation, SEO meta, image alt text, and image generation — no config file changes or server restarts required.

**Secure Media Vault** — A hierarchical media manager backed by a 7-stage upload pipeline: double-extension detection → MIME validation → image sanitization → moderation check → optional ClamAV scan → UUID generation → metadata storage. Folder-level permissions, full audit log, and AI-generated alt text included.

**Granular access control** — 34 permissions across 8 policy classes, cached RBAC, invite-only user flow, and OAuth via Google and GitHub.

---

### What's Included

| Module | Notes |
|---|---|
| Auth & RBAC | Login, registration, email verification, password reset, invitations, role/permission management |
| Social Login | Google and GitHub via Laravel Socialite |
| Pages | CKEditor 5, draft/published workflow, SEO fields, scheduled publishing |
| Banners | Drag-and-drop ordering, `start_at`/`end_at` scheduling |
| The Vault | Hierarchical media manager, 7-stage secure upload pipeline, AI alt text |
| AI Hub | 7 providers, runtime config, usage tracking, text/image/meta generation |
| Markdown for Agents | `Accept: text/markdown` on all public pages, `/sitemap.md`, `/llms.txt`, `/llms-full.txt` |
| Dashboard | Analytics cards, Recharts charts, activity feed |
| Activity Log | Filterable audit trail with before/after state snapshots |
| Settings | Key/value config store, maintenance mode with admin bypass |
| Menus | Drag-and-drop navigation builder |
| Dark Mode | System-preference aware, admin toggle |

---

### Requirements

- PHP 8.4+
- MongoDB 6+
- Node.js 18+ (build only)

---

### Getting Started

```bash
composer run setup
composer run dev
```

Or with Docker:

```bash
docker compose up
```

---

### Security

This release includes mitigations for OWASP Top 10 risks: SSRF protection via `SafeHttpClient`, XSS blocking on banner URLs, image polyglot prevention in the upload pipeline, and admin role self-deletion protection.

To report a security issue, please open a [private advisory](../../security/advisories/new) rather than a public issue.
