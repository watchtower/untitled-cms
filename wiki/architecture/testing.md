# Testing

> Test setup, conventions, and known gotchas.

Last updated: 2026-04-05

## Running tests

```bash
composer run test

# Single file
php artisan test tests/Feature/VaultUploadTest.php
```

## Setup

PHPUnit uses **SQLite in-memory** for all tests. This overrides the MongoDB
connection configured in `.env`. This means:
- No MongoDB required to run tests
- MongoDB-specific query syntax (aggregations, etc.) is not covered by tests
- If you add MongoDB-specific queries, consider whether they need integration tests

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
- Vault upload operations
- Vault folder operations

Currently missing coverage (investigate):
- AI Hub interactions
- Permission enforcement on routes
- Redirect middleware
- MongoDB-specific query behaviour

## CI

The GitHub Actions PHPUnit job runs with:
- A MongoDB service container
- The `mongodb` PHP extension
- SQLite override still active (the MongoDB service is for future integration tests)

## Gotchas

- Tests run against SQLite, not MongoDB. A test passing does not guarantee
  the same code works correctly against MongoDB in production.
- The `public/hot` Vite workaround is fragile — if Vite changes how it detects
  dev mode, this will break silently.

## See also

- [[database/collections]] — SQLite override details
- [[modules/vault]] — what VaultUploadTest is testing
