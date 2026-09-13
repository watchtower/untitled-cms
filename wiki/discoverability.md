# Search Discoverability

> Day-0 search baseline, verified findings, and the keyword surface actually worth pursuing

Last updated: 2026-09-05

Working plan: `docs/seo-discoverability-plan.md`. This page records **verified state**;
the plan records intent. Where they disagree, this page wins.

## Day-0 baseline — 2026-09-05

Measured so later movement is meaningful. Without these numbers nothing is measurable.

| Property | State |
|---|---|
| Repo | `watchtower/untitled-cms` — public, MIT, created 2026-02-08 |
| Stars / forks | **0 / 0** |
| Topics | **none** |
| `homepageUrl` | **empty** |
| GitHub Pages | not enabled (API 404) |
| Packagist | **not published** — `nava/untitled-cms` 302s to `?reason=package_not_found` |
| Google: `site:github.com/watchtower/untitled-cms` | **0 results** — "did not match any documents" |
| Google: `"Untitled CMS"` | repo page absent; three *proxies* rank (see below) |

Verification method matters here. The `site:` query was run in a **real browser**, not a
search API — search APIs silently ignore the `site:` operator and return unrelated results.
Google's own "Try Google Search Console / do you own this?" promo on the results page
confirms the operator was parsed rather than dropped.

## Changes applied

The Day-0 table above is a historical record and is **never edited**. Changes land here.

### 2026-09-05 — Phase 1 repo settings (maintainer-applied)

- **Topics set (11):** `ai`, `ai-native`, `cms`, `content-management`, `headless-cms`,
  `inertiajs`, `laravel`, `llms-txt`, `mongodb`, `php`, `react`. Verified live via
  `gh repo view --json repositoryTopics`.
- **`homepageUrl` set** to `https://github.com/watchtower/untitled-cms#readme` (URL verified
  to return 200 before being used).

Why this matters more than it looks: GitHub topic hub pages are crawled far more heavily than
individual repo pages, and Search Console cannot cover `github.com`. Topic hubs are therefore
the most realistic route to getting the repo page itself discovered and indexed.

**Next measurement:** re-run `site:github.com/watchtower/untitled-cms` in a real browser
**no earlier than 2026-10-05**. Indexing takes 2–4 weeks; re-checking sooner tells you nothing,
and re-submitting changes nothing. Record the result here either way — a still-zero result at
30 days is itself a finding, and points at the naming/inbound-link problem rather than metadata.

## The repo page is not indexed, but the project is

This is the important nuance and it is **not** what the plan assumed. The canonical repo
page is absent from Google's index, yet `"Untitled CMS"` returns:

1. **trendshift.io** — a third-party GitHub-stats page for `watchtower/untitled-cms`,
   carrying the full repo description. Ranks first.
2. **github.com/watchtower** — the org page, indexed, carrying the description.
3. **github.com/NavanithanS** — the user page, indexed, carrying the description.

So the description is already circulating; the page that should own the term is the one
missing. This reframes the task from "become visible" to "**own the canonical page for a
term already ranking through proxies**" — a materially easier problem.

## Corrections to the plan's assumptions

> **Superseded:** `docs/seo-discoverability-plan.md` §3 claims *"Search for `"Untitled CMS"`
> returns no competing project of that name."* That is no longer true as of 2026-09-05.

- **`tar6/Untitled-CMS`** exists on GitHub ("Shadow Community Management System") and ranks
  on page 1 for the exact name.
- **`Untitled CMS — Platform Architecture`** (hirammendiola.com) is an unrelated project
  using the same name, also on page 1.
- Non-software noise is heavy: AllMusic, DeviantArt, Instagram, a Burchfield Penney artwork
  all rank for `"Untitled CMS"` — the "untitled is a common word" risk is real and measured.

**New finding the plan did not anticipate — the org name collides too.** `watchtower` is
also `containrrr/watchtower`, a very widely used Docker automation tool with far more
authority. It appears on page 1 of `site:github.com watchtower untitled-cms` and dilutes
every org-level query. The naming question in §3 is therefore *two* questions: the project
name **and** the org name.

## Keyword surface — validated, not assumed

Each term below was searched before being kept or dropped, per plan §7.

| Term | Verdict | Evidence |
|---|---|---|
| `laravel mongodb cms` | **drop** | Page 1 is entirely laravel.com / mongodb.com / `mongodb/laravel-mongodb` docs. Google reads this as a *driver* query; the "cms" token is effectively ignored. Intent mismatch, not just competition. |
| `ai native cms` | **drop** | Has become a contested commercial category since the plan was written — Cosmic, BaseHub, Hygraph, Unily, Ring Publishing, Gitana all on page 1. |
| `llms.txt` + Markdown-for-agents | **pursue** | Page 1 is *all* explainer/blog content (llmstxt.org, Search Engine Land, Medium, dev blogs). **No CMS product owns this surface.** |

The two flagship targets from plan §7 both failed validation. The genuinely thin, exact-match
surface is the **AI-agent delivery angle**: `/llms.txt`, `/llms-full.txt`, and
`Accept: text/markdown` page delivery. That is also the project's real differentiator, so the
positioning and the winnable keyword coincide.

### Distribution signal (worth more than the keyword)

Live demand threads found during validation, all recent and unanswered by an obvious product:

- r/laravel — *"Making your Laravel app AI-agent friendly (llms.txt, markdown responses,
  structured data)"* (10+ comments) — exactly this project's feature set.
- r/selfhosted — *"Any self-hosted AI-native CMS out there?"*
- r/cms — competitors (Noma) launched into this subreddit successfully.

For a repo at 0 stars these are a higher-yield channel than any on-page change.

## Standing constraints

- **Do not commit.** Stage and hand over; the maintainer commits.
- **Do not change public repo settings** (topics, description, homepage, visibility) without
  explicit per-action approval — hand over the exact command instead.
- Packagist reads `composer.json` from the **default branch**, which is `master` here (not
  `main`). Metadata must be pushed to `master` *before* submitting to Packagist.
- Do not hardcode `version` in `composer.json` — the repo carries git tags (`0.1.0`–`0.4.0`)
  and Packagist derives versions from them. A stale pin makes a fresh listing wrong on day one.
- **This package is `"type": "project"`, and that is correct.** It is an application skeleton
  (the README documents `git clone`), not a library. So the Packagist win is
  `composer create-project nava/untitled-cms`, **not** `composer require` — plan §4.3 has this
  wrong. Same pattern as `laravel/laravel`. Do not "fix" the type to make `require` work.
- Repo URL verified to resolve: `https://github.com/watchtower/untitled-cms` returns 200 and
  `full_name` is canonical. The zero-indexation is newness plus zero inbound links, not a
  broken or redirected path.

## See also

- [overview](overview.md)
- [architecture/stack](architecture/stack.md)
