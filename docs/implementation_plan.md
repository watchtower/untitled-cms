# Laravel 12 + MongoDB Smart CMS - Implementation Plan

## Confirmed Stack

| Component | Choice |
|-----------|--------|
| Backend | Laravel 12 + Official MongoDB driver |
| Frontend | Inertia.js + React + Shadcn UI |
| AI | Laravel AI SDK (`laravel/ai`) |
| Storage | Local default, R2 optional |
| File Manager | UniSharp (window bridge) |
| Routing | No Ziggy (props-based) |
| Editor | CKEditor 5 (GPL/Open Source) |
| Auth | Permission-based RBAC |
| Deployment | DigitalOcean VPS |
| Database | Self-hosted MongoDB |

---

## Phase Overview

| Phase | Focus | Deliverables |
|-------|-------|--------------|
| **1** | Foundation & Auth | Laravel 12 + MongoDB + Shadcn + RBAC |
| **2** | Content Modules | Pages CRUD with CKEditor GPL |
| **3** | Media & Banners | UniSharp, Banner management |
| **4** | AI Features | Auto SEO, Auto alt-text |
| **5** | Polish & Deploy | DigitalOcean VPS deployment |

---

## Phase 1: Foundation & Auth

> **Goal**: Working Laravel 12 + MongoDB + Inertia + Shadcn skeleton with RBAC

| # | Task | Details |
|---|------|---------|
| 1.1 | Project setup | `laravel new`, MongoDB driver, Breeze React |
| 1.2 | Shadcn UI setup | Install CLI, add base components |
| 1.3 | Auth layout | Sidebar navigation, user dropdown |
| 1.4 | MongoDB config | Connection, indexes |
| 1.5 | RBAC system | `Role`, `HasRoles` trait, permissions middleware |
| 1.6 | Users CRUD | List, create, edit, delete, assign roles |
| 1.7 | Roles CRUD | Manage roles + permissions |
| 1.8 | Dashboard | Basic stats placeholder |

---

## Phase 2: Content Modules (Pages)

> **Goal**: Full WebPages CRUD with CKEditor

| # | Task | Details |
|---|------|---------|
| 2.1 | Page model | Schema, validation, slugs |
| 2.2 | Pages CRUD | List (datatable), create, edit, delete |
| 2.3 | CKEditor integration | GPL build, custom upload adapter |
| 2.4 | Status workflow | Draft → Published toggle |
| 2.5 | SEO fields | Manual meta title/description input |
| 2.6 | Permissions | `pages.view`, `pages.create`, `pages.edit`, `pages.delete`, `pages.publish` |

---

## Phase 3: Media & Banners

> **Goal**: Media library and banner management

| # | Task | Details |
|---|------|---------|
| 3.1 | UniSharp setup | Config, R2 disk option |
| 3.2 | Media library UI | React wrapper for LFM |
| 3.3 | Media collection | Store metadata in MongoDB |
| 3.4 | Banner model | Schema with dates, position, order |
| 3.5 | Banners CRUD | List, create, edit, delete, reorder |
| 3.6 | Image picker | Select from media library |

---

## Phase 4: AI Features

> **Goal**: Laravel AI SDK integration for smart content

| # | Task | Details |
|---|------|---------|
| 4.1 | Laravel AI setup | Install, configure provider (OpenAI/Anthropic) |
| 4.2 | Mock mode | Local dev without API costs |
| 4.3 | Auto SEO job | Background job on page save |
| 4.4 | Auto alt-text job | On image upload |
| 4.5 | UI integration | "Generate SEO" button, AI status indicators |

### AI Features Scope

| Feature | Phase | Implementation |
|---------|-------|----------------|
| Auto SEO (title + description) | 4 ✅ | Job on save, user can override |
| Auto alt-text | 4 ✅ | Job on upload |
| Auto-tagging | Future | Requires taxonomy system |
| Semantic search | Future | Requires Atlas Vector Search |

---

## Phase 5: Polish & Deploy

> **Goal**: Production-ready deployment

| # | Task | Details |
|---|------|---------|
| 5.1 | Error handling | 404/500 pages, validation messages |
| 5.2 | Activity log | Track user actions (MongoDB-compatible) |
| 5.3 | Cache layer | Redis for sessions, permissions |
| 5.4 | Testing | Key feature tests with Pest |
| 5.5 | Deploy script | DigitalOcean VPS setup |
| 5.6 | MongoDB backup | mongodump cron job |

---

## MongoDB Schema

### Users
```javascript
{
  _id: ObjectId,
  name: String,
  email: String,
  password: String,
  roles: [ObjectId],
  avatar: String,
  created_at: Date,
  updated_at: Date
}
```

### Roles
```javascript
{
  _id: ObjectId,
  name: String,
  slug: String,
  permissions: [String],
  created_at: Date,
  updated_at: Date
}
```

### Pages
```javascript
{
  _id: ObjectId,
  title: String,
  slug: String,
  body: String,
  status: String,
  author_id: ObjectId,
  featured_image: { path: String, alt: String },
  seo: { title: String, description: String, og_image: String },
  published_at: Date,
  created_at: Date,
  updated_at: Date
}
```

### Media
```javascript
{
  _id: ObjectId,
  filename: String,
  original_name: String,
  path: String,
  disk: String,
  mime_type: String,
  size: Integer,
  alt_text: String,
  metadata: Object,
  uploaded_by: ObjectId,
  created_at: Date,
  updated_at: Date
}
```

### Banners
```javascript
{
  _id: ObjectId,
  title: String,
  subtitle: String,
  image: { path: String, alt: String },
  cta: { text: String, url: String },
  position: String,
  status: String,
  start_date: Date,
  end_date: Date,
  order: Integer,
  created_at: Date,
  updated_at: Date
}
```

---

## Permission Matrix

| Permission | Admin | Editor | Author |
|------------|-------|--------|--------|
| `users.manage` | ✅ | ❌ | ❌ |
| `roles.manage` | ✅ | ❌ | ❌ |
| `pages.view` | ✅ | ✅ | ✅ |
| `pages.create` | ✅ | ✅ | ✅ |
| `pages.edit` | ✅ | ✅ | own |
| `pages.delete` | ✅ | ✅ | ❌ |
| `pages.publish` | ✅ | ✅ | ❌ |
| `media.upload` | ✅ | ✅ | ✅ |
| `media.delete` | ✅ | ✅ | ❌ |
| `banners.manage` | ✅ | ✅ | ❌ |

---

## Tech Stack

### Composer
```
laravel/framework:^12.0
mongodb/laravel-mongodb
inertiajs/inertia-laravel
laravel/breeze
laravel/ai
unisharp/laravel-filemanager
league/flysystem-aws-s3-v3
```

### NPM
```
react react-dom
@inertiajs/react
tailwindcss
shadcn/ui (CLI)
lucide-react
clsx tailwind-merge class-variance-authority
```
