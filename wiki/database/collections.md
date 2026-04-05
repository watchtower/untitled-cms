# Collections

> MongoDB models, collections, and conventions.

Last updated: 2026-04-05 (added email_logs, suppressed_emails)

## Overview

MongoDB is required for production. Tests override to SQLite in-memory (configured
in `phpunit.xml`). The `mongodb/laravel-mongodb` package provides Eloquent-compatible
model syntax.

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
| `vault_folders` | Vault directory structure |
| `activity_logs` | Audit trail of user actions |
| `ai_hubs` | AI provider configurations |
| `chat_sessions` | AI chat history |
| `menus` | Navigation menu definitions |
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
adds a caching layer. See [[modules/services]].

## See also

- [[modules/services]] — SettingsService caching layer
- [[modules/vault]] — how vault_files records are created
- [[architecture/testing]] — SQLite override in tests
