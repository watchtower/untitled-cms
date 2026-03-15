# Contributing to Untitled CMS

Thank you for considering a contribution! Please take a moment to review these guidelines.

## Getting Started

1. Fork the repository and clone your fork.
2. Copy `.env.example` to `.env` and configure MongoDB.
3. Run the setup script:
    ```bash
    composer run setup
    ```
4. Start dev services:
    ```bash
    composer run dev
    ```

## Development Workflow

- Branch from `main` for all changes.
- Use [Conventional Commits](https://www.conventionalcommits.org/) for commit messages:
    ```
    feat(vault): add folder color labels
    fix(auth): handle expired social login tokens
    docs: update installation guide
    ```
- Run tests before opening a PR:
    ```bash
    composer run test
    ```
- Run the PHP formatter:
    ```bash
    ./vendor/bin/pint
    ```

## Adding a New Module

A "module" in Untitled CMS is a Model + Controller + Policy + Inertia page set. Follow these conventions:

### Backend

1. **Model** (`app/Models/`): extend `MongoDB\Laravel\Eloquent\Model`, set `$connection = 'mongodb'` and `$collection`.
2. **Controller** (`app/Http/Controllers/`): use `$this->authorize(...)` for every action.
3. **Policy** (`app/Policies/`): register in `AuthServiceProvider` (or Laravel's auto-discovery).
4. **Permissions**: add new `module.action` strings to `Role::availablePermissions()` in `app/Models/Role.php`. Keep this as the single source of truth.
5. **Seeder**: update `RoleSeeder` to grant new permissions to the appropriate roles.
6. **Routes**: add resource routes to `routes/web.php` inside the `auth` + `verified` middleware group.

### Frontend

1. Create a page under `resources/js/Pages/YourModule/`.
2. Use Inertia props — no separate API calls.
3. Follow existing table/form patterns (TanStack Table, Zod validation, Sonner toasts).

## Code Style

- **PHP**: PSR-12 via Laravel Pint (`./vendor/bin/pint`).
- **TypeScript/React**: ESLint + Prettier (configured in `eslint.config.js`).
- **Strict types**: all new PHP files must include `declare(strict_types=1);`.
- **No debug code**: remove `dd()`, `dump()`, `console.log()`, `console.error()` before opening a PR.

## Pull Request Checklist

- [ ] Tests pass (`composer run test`)
- [ ] Pint has no formatting errors
- [ ] No secrets or API keys committed
- [ ] New permissions added to `Role::availablePermissions()` and `RoleSeeder`
- [ ] PR description explains _why_, not just _what_

## Reporting Bugs

<!-- TODO: replace the URL below with your real repository URL before open-source release -->

Open a [GitHub Issue](https://github.com/watchtower/untitled-cms/issues). For security vulnerabilities, see [SECURITY.md](SECURITY.md).
