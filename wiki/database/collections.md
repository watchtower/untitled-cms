# Collections

> MongoDB models, collections, and conventions.

Last updated: 2026-09-13 (vault_folders unique index, menus item shape)

## Menu item shape

`MenuController` validates every persisted key in `items[]`. Laravel's `validated()` drops nested
keys that have no rule, so a new item field (e.g. `icon`) needs a rule in `menuItemRules()`, or it is
silently discarded on save. `Menus/Edit.tsx`, `MenuSeeder` and `PublicLayout` all use this shape.

## Overview

MongoDB is required for production **and for tests**. `phpunit.xml` switches only the default
connection to SQLite, and models pin `mongodb`, so tests use the MongoDB server from `.env`
(see [architecture/testing](../architecture/testing.md)). The `mongodb/laravel-mongodb` package provides
Eloquent-compatible model syntax.

## Model conventions

Every MongoDB model must declare:

```php
protected $connection = 'mongodb';
protected $collection = 'collection_name';
```

Use `mongodb/laravel-mongodb` relationship methods (not standard Eloquent ones) where
the implementation differs. Check the package docs when setting up new relationships.

## Collections

| Collection | Purpose |
|-----------|---------|
| `users` | User accounts |
| `roles` | Role definitions with permission arrays |
| `pages` | CMS content pages |
| `banners` | Banner/announcement records |
| `vault_files` | Uploaded file metadata |
| `vault_folders` | Vault directory structure. Unique index `vault_folders_parent_name_unique` on `(parent_id, name, deleted_at)` |
| `activity_logs` | Audit trail of user actions |
| `ai_hubs` | AI provider configurations |
| `chat_sessions` | AI chat history |
| `menus` | Navigation menu definitions; `items[]` = `{id, title, url, target, order, subItems[]}` |
| `settings` | Key/value site settings |
| `redirects` | URL redirect rules |
| `email_logs` | Outbound email delivery records (status, timestamps, resend_id) |
| `suppressed_emails` | Addresses blocked from receiving email (bounced, complained, unsubscribed) |

## .env configuration

```env
DB_CONNECTION=mongodb
DB_HOST=127.0.0.1
DB_PORT=27017
DB_DATABASE=untitled_cms
```

## Settings access

Don't query the `settings` collection directly. Use `SettingsService` which
adds a caching layer. See [modules/services](../modules/services.md).

## See also

- [modules/services](../modules/services.md) — SettingsService caching layer
- [modules/vault](../modules/vault.md) — how vault_files records are created
- [architecture/testing](../architecture/testing.md) — SQLite override in tests
