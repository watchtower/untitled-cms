# 🚀 Untitled CMS (Laravel + MongoDB + React)

A modern, highly-capable Content Management System built on a powerful monolithic SPA architecture. It combines the robust backend capabilities of **Laravel 12** and **MongoDB** with the fluid, dynamic frontend experience of **React**, **Inertia.js**, and **Shadcn UI**.

---

## ✨ Features

- **Headless-Ready SPA**: Built with Inertia.js, delivering a lightning-fast single-page application experience without the complexity of a separate API layer.
- **MongoDB Backend**: Takes advantage of Document-oriented storage using `mongodb/laravel-mongodb` for flexible, schema-less data structures.
- **Advanced Role-Based Access Control (RBAC)**: Granular permissions system to manage access for Admins, Editors, Authors, and custom roles.
- **Rich Content Management**:
  - **Pages**: Create dynamic pages using **CKEditor 5**. Features include SEO meta fields, Draft/Published workflows, and dynamic routing.
  - **Banners**: Manage promotional banners with scheduling capabilities and visually intuitive **drag-and-drop** reordering (via `@dnd-kit`).
- **The Vault (Media Manager)**: 
  - Integrated, highly secure file manager with folder organization.
  - Strict file uploading pipeline: MIME validation, double-extension detection, image sanitization, and sandboxed scanning.
- **AI-Powered Workflows**: Integrated with `openai-php` to automatically generate SEO titles, meta descriptions, and image alt-text.
- **Comprehensive Audit Trail**: Integrated **Activity Logging** ensures every content change or administrative action is securely tracked.
- **Modern UI/UX**: 
  - Beautifully designed accessible components via **Shadcn UI** & **Tailwind CSS v4**.
  - Built-in Dark Mode support.
  - Interactive data tables using **TanStack Table** and analytics with **Recharts**.

---

## 🛠 Tech Stack

### Backend
- **Framework**: [Laravel 12.x](https://laravel.com/) (PHP 8.2+)
- **Database**: [MongoDB](https://www.mongodb.com/) (using `mongodb/laravel-mongodb`)
- **Authentication**: Laravel Sanctum
- **Security**: `mews/purifier` for rigorous HTML sanitization

### Frontend
- **Framework**: [React 18](https://reactjs.org/) + TypeScript
- **Routing/Bridge**: [Inertia.js v2.0](https://inertiajs.com/) & Ziggy
- **Styling**: [Tailwind CSS v4](https://tailwindcss.com/)
- **Components**: [Shadcn UI](https://ui.shadcn.com/) (Radix Primitives)
- **Editor**: [CKEditor 5](https://ckeditor.com/)

---

## 📦 Requirements

Before running the project, ensure you have the following installed:
- **PHP** >= 8.2
- **Composer**
- **Node.js** (v18 or higher) & **NPM**
- **MongoDB** Server (Local or Atlas)

---

## 🚀 Installation & Setup

1. **Clone the repository**
   ```bash
   git clone <your-repo-url>
   cd untitled-cms
   ```

2. **Install PHP Dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript Dependencies**
   ```bash
   npm install
   ```

4. **Environment Configuration**
   Copy the example `.env` file and configure your database and third-party keys.
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   > **Note**: Ensure `SESSION_DRIVER=file` or `redis` is used for single-server MongoDB setups to avoid key conflicts. Add your `OPENAI_API_KEY` for AI features and `TINYMCE_API_KEY` if utilizing alternative editors.

5. **Run Migrations & Seeders**
   This sets up your MongoDB collections and populates the database with default Roles, Permissions, and an Admin user.
   ```bash
   php artisan migrate --force
   php artisan db:seed
   ```

6. **Build Frontend Assets**
   ```bash
   npm run build
   ```
   *(Or run `npm run dev` for hot-module replacement during development)*

7. **Serve the Application**
   ```bash
   php artisan serve
   ```

---

## 🔑 Default Login Credentials

Once seeded, you can log in at `http://localhost:8000/login`:
- **Email**: `admin@example.com`
- **Password**: `password`

---

## 🧑‍💻 Development Scripts

- `npm run dev` - Starts the Vite development server with HMR.
- `npm run build` - Builds the application for production.
- `./vendor/bin/pint` - Runs Laravel Pint to format PHP code and fix styling issues.

---

## 🚢 Deployment

For detailed production deployment instructions (including DigitalOcean droplets, Cloudflare configuration, and CI/CD pipelines), please refer to the [DEPLOYMENT.md](DEPLOYMENT.md) guide.

---

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
