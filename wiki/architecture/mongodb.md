# MongoDB decision

> Why production uses MongoDB, how tests differ, and when to re-evaluate.

Last updated: 2026-07-12

## Decision

**Production database is MongoDB** via `mongodb/laravel-mongodb`. All app models set
`protected $connection = 'mongodb'` and a collection name. There is no dual-write to SQL
in production.

## Why MongoDB

- Content shapes vary (pages body HTML, banner slides, menu trees, settings JSON)
- Vault metadata and AI hub config fit document storage well
- Fewer migrations for nested/flexible fields during early product evolution

## Costs / trade-offs

- Relational integrity and multi-document transactions are weaker / different than SQL
- Team must understand Mongo indexes (e.g. redirects, email logs, vault search)
- Eloquent relationship internals differ from classic SQL Laravel apps
- **Test dual-path:** PHPUnit uses SQLite in-memory (see [architecture/testing](testing.md)); CI may still run a Mongo service for the suite environment, but local tests favor SQLite speed

## Test gap

SQLite tests will not catch:

- Mongo-specific query operators or index requirements
- Collection-level quirks with soft deletes / ObjectId casting
- Production index miss latency

Mitigation: keep feature tests for authz and workflows; add targeted Mongo integration
tests only when a bug is Mongo-specific; document indexes in [database/collections](../database/collections.md).

## Re-evaluate if

- Multi-tenant reporting needs heavy relational joins
- Compliance requires SQL-only ops tooling
- Team no longer has Mongo operational capacity
- A clear migration ROI appears (not planned in the hardening epic)

## Non-decision

This page does **not** authorize migrating off MongoDB. It records intent and gaps only.

## See also

- [architecture/stack](stack.md) — stack summary
- [architecture/testing](testing.md) — SQLite override and gotchas
- [database/collections](../database/collections.md) — models and collections
