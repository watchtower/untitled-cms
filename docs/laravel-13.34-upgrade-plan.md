# Laravel 13.34 Upgrade Plan — October 2026

Status: **Planned** 2026-10-03. Branch: `chore/laravel-13.34-upgrade` off `master` (12843d9).
Source review: Laravel OSS changelog through the 2026-09 digest, see [wiki/architecture/upgrade-tracker.md](../wiki/architecture/upgrade-tracker.md).
Each phase is staged separately and must pass the verification gate before the next one begins.

---

## Baseline (2026-10-03)

| Check | Result |
|---|---|
| `laravel/framework` | v13.31.0 (latest v13.34.0) |
| `php artisan test` | 97 passed, 245 assertions |
| Other direct packages behind | inertia-laravel 3.3.4, phpunit 13.3.3, resend-laravel 1.4.0, sail 1.67.0, intervention/image 4.3.2, htmlpurifier 4.19.0, `laravel/ai` 0.11.2, guzzle 7.15.5 |

Before Phase 1, also capture `composer audit`, `npx tsc --noEmit` and `npm run build` (bundle sizes).

---

## Decisions

| Item | Decision | Why |
|---|---|---|
| **Framework 13.34** | Upgrade | Patch/minor within `^13.0`. The 2026-09 digest lists only additive features. |
| **`laravel/ai` 1.0** | Upgrade, separate phase | Our call signatures are unchanged in 1.0.1 (verified, see Phase 2). The high-impact 1.0 changes cover features we don't use. |
| **Guzzle 8** | Out of scope (still blocked) | `laravel/socialite` → `league/oauth1-client` 1.11 caps at `^6\|^7`. |
| **New framework features** | Not adopted in this branch | Listed under Follow-ups so the upgrade stays a pure version bump. |

---

## Phase 1 — Framework and in-range updates

Low risk. No constraint changes.

```bash
composer update laravel/framework inertiajs/inertia-laravel phpunit/phpunit \
  resend/resend-laravel laravel/sail intervention/image ezyang/htmlpurifier -W
```

- Check the `laravel/framework` 13.32–13.34 GitHub release notes for behaviour changes (the OSS changelog lists features only).
- Check the `inertia-laravel` 3.4 / 3.5 release notes. Shared props in `HandleInertiaRequests` must still match `types/global.d.ts`.
- PHPUnit 13.4: run once with `--display-deprecations` and fix anything new.

## Phase 2 — `laravel/ai` ^0.11 → ^1.0

```bash
composer require laravel/ai:^1.0 -W
```

**Verified against v1.0.1 source:** `new AnonymousAgent(string $instructions, iterable $messages, iterable $tools)`,
`prompt($prompt, array $attachments = [], $provider = null, ?string $model = null, ?int $timeout = null)` (`$prompt` is widened, which is backward compatible),
`new Base64Image(string $base64, ?string $mimeType = null)`.
Call sites: `app/Services/AiService.php` (~71, 220, 244, 291, 334, 350) and `app/Http/Controllers/AiController.php:174`.

How the 1.0 upgrade guide applies to us:

| 1.0 change | Impact | Notes |
|---|---|---|
| Laravel MCP 1.0 required | None | `laravel/mcp` is not installed |
| Conversations store `steps` / `status` | None | No `RemembersConversations` and no `agent_conversation_*` tables. Chat history lives in our own `chat_sessions` collection. |
| Agent middleware wraps each step | None | No agent middleware |
| Token usage renamed (`inputTokens` / `outputTokens`) | None | We count requests (`monthly_usage`) and never read `Usage` |
| **AWS SDK no longer installed** | **Check** | `aws/aws-sdk-php` is only pulled in by `laravel/ai` and will be removed. The Bedrock provider then throws `RuntimeException`. Our hub picks any provider in `config('ai.providers')`, so decide: require `aws/aws-sdk-php` directly, or reject `bedrock` in `AiService::configureActiveAi()`. Confirm no S3 disk depends on it. |
| **Gemini moves to the Interactions API** | **Smoke test** | We pass no raw provider options, but every Gemini text and vision call takes a new code path. Test with a real Gemini key. |
| Default models changed (OpenAI GPT-6, Anthropic Opus 5.5) | Low | We always pass `$activeHub->default_model`. Check hubs with an empty `default_model`. |
| Streaming / AG-UI / sub-agent changes | None | No streaming through `laravel/ai` |

- `configureActiveAi()` overrides `config('ai.providers.*')` at runtime. Confirm this still takes effect in 1.0 (the manager may cache resolved providers).
  `Ai::build()` is the supported replacement; see Follow-ups.
- Laravel Boost's `/upgrade-ai-sdk-v1` command could automate this, but Boost is not installed and isn't needed for our small surface.

## Verification gate (every phase)

```bash
./vendor/bin/pint --test
composer run test            # expect ≥ 97 passing
npx tsc --noEmit && npm run build
composer audit
```

Manual smoke test (`composer run dev`):
- Admin login, pages CRUD with the TinyMCE editor
- Vault upload (image washing path)
- `/llms.txt`, and `/{slug}` with `Accept: text/markdown`
- Phase 2 only: AI SEO meta, content generation, alt text (vision / `Base64Image`), AI chat, and an AI action with revert. Use an OpenAI hub and a Gemini hub.

## Rollback

Each phase is its own commit, so `git revert <sha>` then `composer install` restores the previous lock file.

## Wrap-up

- Update the wiki: [architecture/stack](../wiki/architecture/stack.md) (versions), [modules/ai-hub](../wiki/modules/ai-hub.md) (laravel/ai 1.0, Bedrock decision),
  tick the boxes in [architecture/upgrade-tracker](../wiki/architecture/upgrade-tracker.md), and add a `log.md` entry.
- Add an Outcome section here with final versions and gate results.

## Follow-ups (separate branches)

- Replace the runtime `config([...])` override in `configureActiveAi()` with `Ai::build(['driver' => ..., 'key' => ...])` passed as `$provider`.
- Use `Storage::copyToDisk()` / `moveToDisk()` for Vault disk moves.
- Add `#[CountCrashesAsExceptions]` to `OptimizeVaultImageJob`.
- Guzzle 8 once `league/oauth1-client` 2.0 is tagged.
