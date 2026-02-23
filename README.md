# 🚀 Untitled CMS

> A modern, highly-capable Content Management System built on a powerful monolithic SPA architecture. Combines the robust backend of **Laravel 12** & **MongoDB** with the fluid, dynamic frontend of **React**, **Inertia.js**, and **Shadcn UI**.

---

## ✨ Features

### 🔐 Authentication & User Management
- **Auth Flows**: Login, Registration, Password Reset, Email Verification
- **Invitation System**: Invite users via email with token-based onboarding
- **Profile Management**: Update name, email, avatar, and password
- **Advanced RBAC**: Granular role-based permissions (Admin, Editor, Author, custom roles)
- **User Policies**: Laravel Gate-based authorization across all controllers

### 📄 Content Management
- **Pages**: Full CRUD with CKEditor 5, SEO meta fields, Draft/Published workflow, dynamic public routing, banner gallery with Embla Carousel
- **Banners**: Create/edit with drag-and-drop reordering (`@dnd-kit`), scheduling, and image upload

### 🗄️ The Vault (Media Manager)
- Hierarchical folder structure with drag-and-drop organization
- Secure upload pipeline: MIME validation, double-extension detection, image sanitization
- Resizable 3-panel layout (sidebar / grid / preview) via `react-resizable-panels`
- Folder-level permissions per user
- VaultPicker component for selecting media across the app
- Audit logging for all Vault actions

### 🤖 AI Hub
- Multi-provider AI integration manager (OpenAI, Gemini, Stability AI)
- Monthly usage tracking per active hub
- **Text Generation**: SEO meta titles & descriptions, general content writing
- **Vision**: Auto alt-text generation from uploaded images (Gemini, GPT-4o)
- **Image Generation**: DALL-E 3 (OpenAI), Stable Diffusion XL (Stability), Imagen 3 / Gemini Flash Image

### 📊 Dashboard & Analytics
- Stats cards and activity charts using **Recharts**
- Recent activity feed

### 🔍 Activity Log
- Comprehensive audit trail for all admin actions
- Filterable log viewer in the admin panel

### 🌐 Public Site
- Headless-ready public page renderer
- Full-width responsive image carousel for page banners
- SEO meta tags, dynamic routing via `Redirect` model

### ⚙️ Settings
- Site-wide settings management via `Setting` model (`key/value` store)
- Accessible via `SettingsService` for global config

---

## 🛠 Tech Stack

### Backend
| Package | Version | Purpose |
|---|---|---|
| [Laravel](https://laravel.com/) | `^12.0` | Core framework |
| [mongodb/laravel-mongodb](https://github.com/mongodb/laravel-mongodb) | `^5.5` | MongoDB ODM driver |
| [laravel/sanctum](https://laravel.com/docs/sanctum) | `^4.0` | Session/token authentication |
| [laravel/ai](https://github.com/laravel/ai) | `^0.2.1` | LLM provider abstraction |
| [inertiajs/inertia-laravel](https://inertiajs.com/) | `^2.0` | SPA bridge |
| [mews/purifier](https://github.com/mewebstudio/Purifier) | `^3.4` | HTML sanitization |
| [tightenco/ziggy](https://github.com/tighten/ziggy) | `^2.0` | Named routes in JS |

### Frontend
| Package | Version | Purpose |
|---|---|---|
| [React](https://reactjs.org/) | `^18.2` | UI framework |
| TypeScript | `^5.0` | Type safety |
| [Tailwind CSS](https://tailwindcss.com/) | `v3/v4` | Utility-first styling |
| [Shadcn UI](https://ui.shadcn.com/) | latest | Accessible component library (Radix primitives) |
| [CKEditor 5](https://ckeditor.com/) | `^41` | Rich text editor |
| [@dnd-kit](https://dndkit.com/) | `^6` | Drag-and-drop |
| [@tanstack/react-table](https://tanstack.com/table) | `^8` | Headless data tables |
| [Recharts](https://recharts.org/) | `^2` | Analytics charts |
| [Embla Carousel](https://www.embla-carousel.com/) | `^8` | Image carousel |
| [react-resizable-panels](https://github.com/bvaughn/react-resizable-panels) | `^4` | Resizable panel layouts |
| [Sonner](https://sonner.emilkowal.ski/) | `^2` | Toast notifications |
| [Zod](https://zod.dev/) | `^4` | Schema validation |

---

## 📦 Requirements

- **PHP** >= 8.2 + **Composer**
- **Node.js** v18+ & **NPM**
- **MongoDB** Server (Local or [Atlas](https://www.mongodb.com/atlas))

---

## 🚀 Installation & Setup

### One-Command Setup
```bash
composer run setup
```
This runs: `composer install` → `.env` copy → `key:generate` → `migrate` → `npm install` → `npm run build`

### Manual Setup

1. **Clone & enter the repo**
   ```bash
   git clone <your-repo-url>
   cd untitled-cms
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Edit `.env` and set:
   ```env
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
   Populates default Roles, Permissions, and an Admin user.

5. **Build & serve**
   ```bash
   npm run build
   php artisan serve
   ```

### Development (all-in-one)
```bash
composer run dev
```
Starts: Laravel server + queue worker + Pail log viewer + Vite HMR — concurrently.

---

## 🔑 Default Login

| URL | Email | Password |
|---|---|---|
| `http://localhost:8000/login` | `admin@example.com` | `password` |

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

See [DEPLOYMENT.md](DEPLOYMENT.md) for detailed production instructions (DigitalOcean, Cloudflare, CI/CD).

---

## 📋 Project TODO

> Tracks planned features, integrations, and architectural decisions.

---

### ✅ Completed

- [x] **Authentication**
  - [x] Login, Registration, Forgot/Reset Password
  - [x] User invitation flow (token-based)
  - [x] Email verification (base Laravel flow)
- [x] **RBAC / Permissions**
  - [x] Roles & Permissions CRUD
  - [x] Laravel Gate authorization across all controllers
  - [x] Granular per-user policies (`UserPolicy`, `PagePolicy`, `BannerPolicy`, `VaultFolderPolicy`, etc.)
- [x] **Users Module**
  - [x] Full CRUD with sortable/searchable table
  - [x] Soft-delete & restore
  - [x] Avatar upload
- [x] **Pages Module**
  - [x] CRUD with CKEditor 5 rich text editor
  - [x] SEO meta fields (title, description)
  - [x] Draft / Published workflow
  - [x] Dynamic public page routing
  - [x] Banner gallery (Embla Carousel) on public pages
- [x] **Banners Module**
  - [x] CRUD with image upload
  - [x] Drag-and-drop reordering (`@dnd-kit`)
  - [x] Active / Inactive scheduling
- [x] **The Vault (Media Manager)**
  - [x] Hierarchical folder structure
  - [x] Secure upload (MIME validation, double-extension detection, image sanitization)
  - [x] Resizable 3-panel layout
  - [x] Context menu (rename, move, delete, download)
  - [x] VaultPicker — reusable media selection component
  - [x] Folder-level user permissions
  - [x] Vault audit log
- [x] **AI Hub**
  - [x] Multi-provider hub manager (OpenAI, Gemini, Stability AI)
  - [x] Monthly usage tracking per hub
  - [x] SEO meta generation from page content
  - [x] Alt-text generation for images (vision: Gemini, GPT-4o)
  - [x] AI text generation (content writing assistant)
  - [x] AI image generation (DALL-E 3, Imagen 3, Stable Diffusion XL)
- [x] **Dashboard**
  - [x] Analytics cards + Recharts graphs
  - [x] Recent activity feed
- [x] **Activity Log**
  - [x] Audit trail for all admin actions
  - [x] Log viewer in admin panel
- [x] **Profile**
  - [x] Update name, email, password
  - [x] Avatar management
- [x] **Settings**
  - [x] Admin-configurable site settings (key/value store)
- [x] **UI/UX**
  - [x] Shadcn UI component library
  - [x] Dark mode support
  - [x] Sortable data tables with `@tanstack/react-table`
  - [x] Toast notifications (Sonner)
  - [x] Responsive layouts

---

### 🔲 In Progress / Planned

#### 🤖 AI Integrations — Expand Provider Support
- [ ] **AI**
  - [ ] [Cohere](https://dashboard.cohere.com) integration
  - [ ] [AI21 Studio](https://studio.ai21.com/auth) integration
  - [ ] [Hugging Face](https://huggingface.co) integration

#### ✉️ Verification & Transactional Email
- [ ] **Verification**
  - [ ] Email resend functionality (resend verification email action)
  - [ ] [Sent.dm](https://www.sent.dm/en) transactional email provider integration

#### 🔔 Notifications
- [ ] **Notifications**
  - [ ] Notification system design (in-app / email / push?)
  - [ ] Notification preferences per user
  - [ ] Real-time notification delivery

#### 🏗️ Admin Module Standard Template
- [ ] **Admin module standard template**
  - [ ] Document the standard module structure (Controller, Model, Policy, Frontend Pages, Routes)
  - [ ] AI to reference this template before scaffolding any new module
  - [ ] Create `/create_admin_module` workflow steps based on finalized template

---

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
