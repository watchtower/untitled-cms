# Upgrade Tracker

> Laravel changelog review checkpoint, plus what is still pending upgrade

Last updated: 2026-10-03

## Source

- Human page: https://laravel.com/framework/docs/changelog (feature notes only, no version numbers)
- Feed: https://laravel.com/rss/oss-changelog (Atom, ~366 entries back to 2025-06).
  The feed's `<updated>` and entry `<id>`s carry dates (`#item-YYYY-MM-DD-<slug>`), so they work as a checkpoint.
  Entries arrive as a **monthly digest**, all stamped on the month's last day (e.g. `2026-09-30T23:59:59`).
- Version numbers come from `composer outdated --direct`, not the changelog.

## Checkpoint

| Field | Value |
|---|---|
| Last reviewed | 2026-10-03 |
| Feed `<updated>` at review | `2026-09-30T23:59:59+00:00` |
| Newest entry seen | `item-2026-09-30-laravel-framework-13x-semantic-and-hybrid-search-for-typesense` |
| Digests covered | everything up to and including **2026-09** |
| Installed framework | v13.34.0 (Phase 1, 2026-10-03) |
| Previous review | 2026-09-13, see `docs/dependency-upgrade-plan.md` |

List entries newer than the checkpoint:

```bash
curl -sL https://laravel.com/rss/oss-changelog | python3 -c '
import sys, xml.etree.ElementTree as ET
ns = {"a": "http://www.w3.org/2005/Atom"}
for e in ET.parse(sys.stdin).getroot().findall("a:entry", ns):
    if e.findtext("a:updated", namespaces=ns) > "2026-09-30T23:59:59+00:00":
        print(e.findtext("a:updated", namespaces=ns)[:10], e.findtext("a:title", namespaces=ns))'
```

After a review, bump the checkpoint table and the date in the command above.

## Pending

Branch: `chore/laravel-13.34-upgrade`. Full plan: [docs/laravel-13.34-upgrade-plan.md](../../docs/laravel-13.34-upgrade-plan.md). Phase 1 done 2026-10-03; Phase 2 pending.

**Phase 1: minor/patch releases (done 2026-10-03)**
- [x] laravel/framework 13.31.0 → 13.34.0, inertiajs/inertia-laravel 3.3.4 → 3.5.1 (+ `@inertiajs/react` 3.8.0)
- [x] phpunit 13.4.0, resend-laravel 1.6.0, sail 1.68.0, intervention/image 4.3.3, htmlpurifier 4.19.1
- [x] league/commonmark 2.10.1 → 2.10.3 (two advisories: DisallowedRawHtml bypass, GFM table DoS)
- [x] Gate green: 97 tests, Pint, tsc, build, `composer audit` clean

**Known and accepted:** `npm audit --omit=dev` reports 7 highs, all coming through the `shadcn` CLI
(`fast-glob` → `micromatch` → `braces`, which has no patched version). The CLI only runs when adding components; at runtime the app imports just `shadcn/tailwind.css`.
Re-check when `braces` is patched.

**Phase 2: `laravel/ai` 0.11.2 → 1.0.1 (major version)**
- [ ] Block the `bedrock` provider in `AiService::configureActiveAi()` (decided 2026-10-03; the AWS SDK is no longer installed)
- [ ] Upgrade guide reviewed 2026-10-03 (see plan). Open items: Bedrock/AWS SDK decision, Gemini smoke test. Touches `AnonymousAgent` / `Base64Image` in `app/Services/AiService.php` and `app/Http/Controllers/AiController.php`
- [ ] Evaluate `Ai::build()` (runtime provider config) for hub-configured providers, see [AI Hub](../modules/ai-hub.md)

**Blocked**
- Guzzle 7 → 8: `league/oauth1-client` 1.11 (via `laravel/socialite`) caps at `^7`. Re-check with `composer why guzzlehttp/guzzle`.

**Possibly useful from the 2026-09 digest (not adopted)**
- `Storage::copyToDisk()` / `moveToDisk()` for [Vault](../modules/vault.md) disk moves
- `#[CountCrashesAsExceptions]` for queued jobs (e.g. `OptimizeVaultImageJob`)
- `Mail::assertSentOnce()` / `Notification::assertSentToOnce()` in tests

Not relevant here: MariaDB/MySQL vector and index changes (we use MongoDB), Typesense/Meilisearch Scout, Horizon, Echo Mercure.

## See also

- [Stack](stack.md)
- [Testing](testing.md)
- [AI Hub](../modules/ai-hub.md)
