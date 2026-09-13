# Testing

> Test setup, conventions, and known gotchas.

Last updated: 2026-09-13

## Running tests

```bash
composer run test

# Single file
php artisan test tests/Feature/VaultUploadTest.php
```

## Setup

`phpunit.xml` sets `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:`, but that only changes the
**default** connection. Every app model declares `$connection = 'mongodb'`, so model queries,
raw aggregations and `assertDatabaseHas(..., 'mongodb')` run against a **real MongoDB server**:
- **A reachable MongoDB is required.** Host and port come from `.env` (`DB_HOST`/`DB_PORT`, which phpunit.xml doesn't override).
  Locally this is the `mongodb` Docker container on port 27018.
- The database name is `:memory:` (from phpunit.xml). Tests call `Model::truncate()` in `setUp` instead of relying on a fresh DB.
- Unique indexes created by migrations (e.g. `vault_folders_parent_name_unique`) may be absent there,
  because tests don't run migrations against Mongo.

## Vite manifest workaround

Tests don't build frontend assets. Creating `public/hot` before each test makes
Vite switch to dev-server mode, which skips the manifest lookup. This file is
removed in `tearDown` to avoid side effects.

If you see `ViteManifestNotFoundException` in tests, check that this setup/teardown
is in place in the test class.

## Test coverage

Tests in `tests/Feature/` cover:
- Auth (login, logout, registration)
- Maintenance mode
- Profile management
- Vault upload, trash, and folder operations (incl. restore collisions, force-delete permissions, `folders.list?all=1`)
- Vault policies (`PolicyTest`)
- Menus (real `Edit.tsx` payload shape, dangerous URL schemes) and Banners controllers
- AI chat (mocked `AiService`)
- Public pages as HTML/Markdown and draft preview permissions

`tests/Unit/` covers `AiHttpClient`.

Currently missing coverage (investigate):
- AI Hub provider integrations (real HTTP paths)
- Redirect middleware
- MongoDB-specific query behaviour

## CI

The GitHub Actions PHPUnit job runs with:
- A MongoDB service container (required — model tests hit it)
- The `mongodb` PHP extension
- `DB_CONNECTION=sqlite` from phpunit.xml, which affects only the default (non-Mongo) connection

## Gotchas

- "SQLite in-memory" is misleading: only the default connection is SQLite. If MongoDB is down,
  most feature tests fail with connection errors, not assertion failures.
- The test database lacks migration-created indexes, so behaviour that depends on those constraints
  (duplicate-key races) isn't exercised by the suite.
- The `public/hot` Vite workaround is fragile — if Vite changes how it detects
  dev mode, this will break silently.

## See also

- [database/collections](../database/collections.md) — SQLite override details
- [modules/vault](../modules/vault.md) — what VaultUploadTest is testing
