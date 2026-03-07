# 🧠 Untitled CMS

> A production-ready, AI-native Content Management System built on a modern monolithic SPA stack. Pairs the reliability of **Laravel 12 + MongoDB** with a fluid **React + Inertia.js** frontend, a full-featured **Media Vault**, a multi-provider **AI Hub**, and headless-ready public delivery — including native **Markdown-for-Agents** support.

---

## ✨ What's Inside

| Module | Highlights |
|---|---|
| 🔐 **Auth & RBAC** | Login · Registration · Email verification · Token-based invitations · Granular role/permission system with Laravel Gate policies |
| 📄 **Pages** | CKEditor 5 rich text · Draft/Published workflow · SEO meta fields · AI-generated meta · Dynamic public routing · Scheduled publishing |
| 🖼 **Banners** | Drag-and-drop reordering (`@dnd-kit`) · Active/inactive scheduling with `start_at / end_at` |
| 🗄️ **The Vault** | Hierarchical media manager · 3-panel resizable layout · Secure upload pipeline (MIME validation → double-extension detection → image sanitization → metadata extraction) · Folder-level permissions · Full audit log · AI-generated alt text |
| 🤖 **AI Hub** | Multi-provider manager (OpenAI, Gemini, Stability AI) · Per-hub monthly usage tracking · Text generation · SEO meta generation · Vision-based alt text · Image generation (DALL-E 3, Imagen 3, Stable Diffusion XL) |
| 📡 **Markdown for Agents** | Public pages respond with clean Markdown + YAML frontmatter when `Accept: text/markdown` is sent — ready for AI crawlers, coding assistants, and Cloudflare AI Gateway |
| 📊 **Dashboard** | Analytics cards + Recharts charts · Recent activity feed |
| 🔍 **Activity Log** | Comprehensive audit trail for all admin actions, filterable in the admin panel |
| ⚙️ **Settings** | Site-wide key/value settings store via `SettingsService` · Custom maintenance mode & error pages |

---

## 🛠 Tech Stack

### Backend

| Package | Version | Purpose |
|---|---|---|
| [Laravel](https://laravel.com/) | `^12.0` | Core framework |
| [mongodb/laravel-mongodb](https://github.com/mongodb/laravel-mongodb) | `^5.5` | MongoDB ODM — document-native data model |
| [laravel/sanctum](https://laravel.com/docs/sanctum) | `^4.0` | Session & token authentication |
| [laravel/ai](https://github.com/laravel/ai) | `^0.2.1` | LLM provider abstraction |
| [inertiajs/inertia-laravel](https://inertiajs.com/) | `^2.0` | Server-side SPA bridge |
| [intervention/image](https://image.intervention.io/v3) | `^3.11` | Image processing & sanitization |
| [league/html-to-markdown](https://github.com/thephpleague/html-to-markdown) | `^5.1` | HTML → Markdown conversion for AI delivery |
| [mews/purifier](https://github.com/mewebstudio/Purifier) | `^3.4` | HTML sanitization (HTMLPurifier wrapper) |
| [tightenco/ziggy](https://github.com/tighten/ziggy) | `^2.0` | Named Laravel routes in JavaScript |

### Frontend

| Package | Version | Purpose |
|---|---|---|
| [React](https://reactjs.org/) | `^18.2` | UI framework |
| TypeScript | `^5.0` | Type safety across all components |
| [Tailwind CSS](https://tailwindcss.com/) | `v3 / v4` | Utility-first styling |
| [Shadcn UI](https://ui.shadcn.com/) | latest | Accessible component library (Radix primitives) |
| [CKEditor 5](https://ckeditor.com/) | `^41` | Rich text / WYSIWYG editor |
| [@dnd-kit](https://dndkit.com/) | `^6` | Drag-and-drop (banners, vault) |
| [@tanstack/react-table](https://tanstack.com/table) | `^8` | Headless, sortable data tables |
| [Recharts](https://recharts.org/) | `^2` | Dashboard analytics charts |
| [Embla Carousel](https://www.embla-carousel.com/) | `^8` | Public page banner carousel |
| [react-resizable-panels](https://github.com/bvaughn/react-resizable-panels) | `^4` | Vault 3-panel layout |
| [Sonner](https://sonner.emilkowal.ski/) | `^2` | Toast notifications |
| [Zod](https://zod.dev/) | `^4` | Frontend schema validation |

---

## 📐 Architecture Overview

```
┌─────────────────────────────────────────────────────┐
│                    Public Web                        │
│  / → PublicController (HTML or Markdown response)   │
│  /{slug} → Page with YAML frontmatter               │
└──────────────────┬──────────────────────────────────┘
                   │ Accept: text/markdown
                   ▼
          AI Crawlers / Agents

┌──────────────────────────────────────────────────────┐
│                  Admin SPA                            │
│  Inertia.js + React + TypeScript                     │
│                                                      │
│  Routes → Controllers → MongoDB Models               │
│                     ↓                                │
│  AI Hub → AiService → OpenAI / Gemini / Stability    │
│                     ↓                                │
│  Vault Upload → Pipeline (6 pipes) → Storage         │
└──────────────────────────────────────────────────────┘
```

**Key design decisions:**
- **MongoDB throughout** — Flexible document model for pages (embedded SEO fields), vault metadata, activity logs, and AI usage tracking.
- **Monolithic SPA** — Laravel renders the initial Inertia page; React handles all subsequent navigation. No separate API server.
- **Upload Pipeline** — Vault uploads pass through an ordered `Pipe` chain utilizing a strict `VaultPipelinePayload` DTO: `ValidateMimeType → DetectDoubleExtension → SandboxedScan → SanitizeImage → GenerateUuid → StoreMetadata`.
- **Single Active AI Hub** — One hub is "active" at a time; `AiService` dynamically patches Laravel AI's config at runtime so no restart is required when switching providers.

---

## 📦 Requirements

- **PHP** >= 8.2 + **Composer**
- **Node.js** v18+ & **npm**
- **MongoDB** (local or [Atlas](https://www.mongodb.com/atlas))
- Optional: **Redis** (for session/cache), **Docker** (compose files included)

---

## 🚀 Installation

### One-Command Setup
```bash
composer run setup
```
Runs: `composer install` → `.env` copy → `key:generate` → `migrate` → `npm install` → `npm run build`

---

### Manual Setup

1. **Clone the repo**
   ```bash
   git clone <your-repo-url>
   cd untitled-cms
   ```

2. **Install dependencies**
   ```bash
   composer install && npm install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Set in `.env`:
   ```env
   APP_URL=http://localhost:8000
   DB_CONNECTION=mongodb
   DB_HOST=127.0.0.1
   DB_PORT=27017
   DB_DATABASE=untitled_cms
   SESSION_DRIVER=file   # or redis
   ```

4. **Migrate & seed**
   ```bash
   php artisan migrate --force
   php artisan db:seed
   ```
   Seeds default roles, permissions, and an admin user.

5. **Build & serve**
   ```bash
   npm run build
   php artisan serve
   ```

---

### Docker

```bash
# Development
docker compose -f docker-compose-dev.yml up

# Production
docker compose up
```

---

### Development (all-in-one)
```bash
composer run dev
```
Starts concurrently: Laravel server + queue worker + Pail log viewer + Vite HMR.

---

## 🔑 Default Login

| URL | Email | Password |
|---|---|---|
| `http://localhost:8000/login` | `admin@example.com` | `password` |

> ⚠️ Change this immediately in production!

---

## 📡 Markdown for Agents

Every public page responds with clean Markdown when `Accept: text/markdown` is present:

```bash
# Homepage — returns a Markdown index of recent pages
curl -H "Accept: text/markdown" http://localhost:8000/

# Individual page — returns YAML frontmatter + Markdown content
curl -H "Accept: text/markdown" http://localhost:8000/your-page-slug
```

Response includes structured `Content-Signal` and `x-markdown-tokens` headers for AI pipeline compatibility.

---

## 🤖 AI Hub Setup

1. Navigate to **Admin → AI Hubs**
2. Enter your API key for the provider (OpenAI, Gemini, or Stability AI)
3. Set a default text model and image model
4. Click **Activate**

The system supports one active hub at a time. Monthly API usage is tracked per hub.

---

## 🧑‍💻 Dev Scripts

| Command | Description |
|---|---|
| `npm run dev` | Vite dev server with HMR |
| `npm run build` | Production asset build |
| `composer run dev` | Full dev stack (server + queue + logs + vite) |
| `composer run test` | Run PHPUnit test suite |
| `./vendor/bin/pint` | Laravel Pint PHP code formatter |

---

## 🚢 Deployment

See [docs/implementation_plan.md](docs/implementation_plan.md) for deployment notes.

Deployment scripts are available at the root:
- `deploy.sh` — Git pull + asset build + cache clear
- `backup.sh` — MongoDB backup script

---

## 📋 Feature Roadmap

### ✅ Shipped

- Authentication (Login, Register, Forgot/Reset Password, Email Verification)
- Token-based user invitation flow
- Granular RBAC — Roles, Permissions, Laravel Gate policies
- Users module (CRUD, soft-delete, avatar, batch actions, logout all devices)
- Pages module (CKEditor 5, SEO fields, Draft/Published, dynamic routing)
- Banners module (drag-and-drop reorder, scheduling)
- The Vault — hierarchical media manager with secure upload pipeline
- VaultPicker — reusable media selection component across the app
- Vault folder-level permissions + audit log
- AI Hub — multi-provider manager with usage tracking
- AI text generation, SEO meta generation, vision alt-text, image generation
- Dashboard with Recharts analytics
- Activity log — filterable audit trail
- Markdown-for-Agents (`Accept: text/markdown` + YAML frontmatter)
- Settings — admin-configurable key/value store
- Dark mode, responsive layouts, Shadcn UI
- Maintenance mode with bypass access for admins and custom error pages
- Enhanced security (OWASP Top 10 mitigation, SSRF protection)
- Strict typing and validation via DTOs and Form Requests

### 🔲 Planned

- [ ] **Page versioning** — revision history with diff viewer and restore
- [ ] **Scheduled publishing** — `publish_at` timestamp + artisan cron
- [ ] **Full-text search** — `Cmd+K` command palette across Pages, Users, Vault
- [ ] **RSS / Atom feed** — `GET /feed.xml` for published pages
- [ ] **Webhook system** — Trigger HTTP webhooks on `page.published`, `vault.uploaded`, etc.
- [ ] **API layer** — Sanctum-protected REST API for headless consumption
- [ ] **2FA** — TOTP two-factor authentication for admin accounts
- [ ] **AI content tagging** — Auto-suggest tags from page body text
- [ ] **AI content moderation** — Vision-based flagging in the Vault upload pipeline
- [ ] **Additional AI providers** — Cohere, AI21 Studio, Hugging Face
- [ ] **`/llms.txt`** — Emerging AI-discoverability standard for published pages
- [ ] **Page view tracking** — Anonymous analytics surfaced in the dashboard
- [ ] **Notification system** — In-app / email / push with per-user preferences

---

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).
