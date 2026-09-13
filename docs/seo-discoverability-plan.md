# Search Discoverability Plan — Untitled CMS

**Status:** Step 1 complete (baseline verified 2026-09-05) · Phase 1 §4.1 topics and §4.2 homepage **applied 2026-09-05** · remaining: Packagist submission, social preview, naming decision

> **Verified findings now live in [`wiki/discoverability.md`](../wiki/discoverability.md).**
> That page supersedes §3 (a same-named project now exists) and §7 (both flagship
> keywords failed validation). Read it before acting on this plan.

This is a kickoff brief for a fresh agent session. It ports a discoverability
audit + implementation that was completed on another repository, adapted to this
project's actual state, plus the traps that cost real time there.

> **Start a new session with:** *"Read `docs/seo-discoverability-plan.md` and
> begin with Step 1 (Establish the baseline)."*

---

## 0. Read this first — the method that matters

The single most valuable thing carried over is not a checklist, it is a
**verification discipline**. On the reference project every one of these
produced a confident, wrong conclusion:

| Failure | What actually happened |
|---|---|
| Trusted a search API for `site:` queries | Search APIs and proxies **silently ignore** the `site:` operator and return unrelated results. Only a real browser query settles indexation. |
| Verified a deploy by checking files existed | Sitemap 200, canonicals correct, exclusions working — while **127 internal links 404'd**. Always assert that a *generated link resolves*, not that a file is present. |
| Excluded the changed field from the diff check | A script truncated `description:` in place. The "nothing was altered" check excluded `description` as "intentionally changed" — so it silently destroyed a sentence in four files, twice unnoticed. **Never exempt the field you are mutating from your own verification.** |
| Assumed a fix failed because output didn't change | It had deployed correctly; a *second, independent* cause was still active. Check the build/deploy actually shipped (API status + `Last-Modified`) before re-diagnosing. |
| Inferred a config from a symptom | Concluded a Search Console property was misconfigured from a 404. It wasn't — the user's screenshot had shown the correct URL all along. **Ask or look; don't infer.** |
| Tested a CLI with the bare command name | `ask --version` reported the right version from a *different* install shadowing it on `PATH`. Use `type -a <cmd>` and the explicit binary path. |
| Ran a command without checking the exit code | `py_compile ... || true` with `tail -1` swallowed a hard `SyntaxError`. |

Two more, specific to changing code:

- **Test across every supported runtime.** A backslash inside an f-string is a
  `SyntaxError` before Python 3.12 — invisible if you only run the newest.
- **Bound your regexes.** `re.sub(r'sha256\s+"[^"]+"', ...)` without `count=1`
  rewrote *every* match; verified to collapse 7 distinct hashes into 1.

---

## 1. Baseline — verified 2026-09-05

| Property | State |
|---|---|
| Repo | `watchtower/untitled-cms` — **public**, MIT (detected ✅) |
| Stars / forks | **0** |
| Topics | **none** ❌ |
| `homepageUrl` | **empty** ❌ |
| Description | Already good — *"production-ready, open-source CMS that treats AI as a first-class citizen"* ✅ |
| GitHub Pages | **not enabled** (API 404) |
| Packagist | **not published** — `nava/untitled-cms` 404s, so `composer require` does not work ❌ |
| `docs/` | 11 files, **internal planning** (`plan.txt`, `implementation_plan.md`, `brainstorm-*`) |
| `wiki/` | LLM-wiki pattern present (`index.md`, `log.md`, `SCHEMA.md`) |
| README | 429 lines, badge block first |

Re-verify with:

```bash
gh repo view watchtower/untitled-cms --json visibility,description,homepageUrl,repositoryTopics,licenseInfo,stargazerCount
curl -s -o /dev/null -w "%{http_code}\n" https://packagist.org/packages/nava/untitled-cms
```

**Not yet checked — do this first:** whether the repo is indexed by Google. Run
`site:github.com/watchtower/untitled-cms` **in a real browser**. The reference
project returned *zero* indexed pages, which reframed the whole problem from
"ranking poorly" to "not indexed at all". Assume nothing until you have looked.

---

## 2. How this project differs from the reference

Do not port the previous plan verbatim. Four differences change the work:

**a. There is no docs site yet.** The reference project's hardest problems were
all GitHub Pages/Jekyll traps. None apply until Pages is enabled — but if you
enable it, §5 is mandatory reading first.

**b. `docs/` is internal, not publishable.** It holds planning documents and
brainstorms. On the reference project Pages served the repo root and published
~400 files including agent instruction files. **If Pages is ever enabled here,
`docs/` must be excluded before the first build**, or internal plans go public.

**c. The registry situation is worse.** The reference project's PyPI page was
its *only* page-1 result and carried the brand. Here there is **no Packagist
package at all** — no registry presence to inherit authority from. Publishing to
Packagist is likely a bigger win here than any on-page change.

**d. The competitive picture is far harder.** The reference tool sat in a niche
category. "CMS" is dominated by Strapi, Decap, Concrete, Ghost, Directus,
Payload and WordPress, all with enormous authority. **Do not target
"open source CMS" or "headless CMS".** The realistic surface is the
differentiators: Laravel 13 + MongoDB + Inertia/React + AI-native.

---

## 3. The name — a genuinely open question

Search for `"Untitled CMS"` returns **no competing project of that name**, which
is much better than the reference project faced (48 same-named repos, and a
*deleted* repo outranking it for its own name).

But two risks:

- **"untitled" is a common English word** and a default filename. It will
  collide with generic noise even without a rival project.
- **"Untitled" reads as a placeholder.** For a CMS being pitched as
  "production-ready", a name that means *not yet named* works against the
  positioning. That is a brand judgement, not an SEO one.

**Decide this early — before building content authority on the name.** Renaming
is cheapest at 0 stars and no Packagist package; it will never be cheaper than
right now. Frame it as a test: ship §4, wait 30 days, then search the name. If
it is indexed and reachable, keep it.

---

## 4. Phase 1 — quick wins (do these first, ~1 hour)

Ordered by impact ÷ effort.

1. **Add GitHub topics.** Currently none. Topic hubs are heavily crawled and are
   the most realistic path to getting a *repository* indexed — Search Console
   cannot cover `github.com`. Suggested:
   `cms`, `laravel`, `mongodb`, `inertiajs`, `react`, `ai`, `headless-cms`,
   `php`, `ai-native`, `content-management`, `open-source-cms`
   ```bash
   gh api -X PUT repos/watchtower/untitled-cms/topics --input - <<'JSON'
   {"names":["cms","laravel","mongodb","inertiajs","react","ai","headless-cms","php","ai-native","content-management","open-source-cms"]}
   JSON
   ```
   > In zsh, the `-f 'names[]=...'` form **must be single-quoted** or the shell
   > glob-expands `[]` and fails before `gh` runs. The heredoc avoids it.

2. **Set `homepageUrl`.** Currently empty — a wasted, crawlable outbound link.
   Point it at the docs site once one exists; until then, the repo README anchor.

3. **Publish to Packagist.** Biggest single gain available. Registry pages carry
   high domain authority and get crawled exhaustively. Add `homepage` and
   `support` to `composer.json` at the same time — on the reference project the
   registry page ranked but linked *nowhere*, wasting all of it.

4. **README first-screen.** 429 lines opening with a badge block. Move a
   one-paragraph "what this is and who it's for" above the badges: it becomes
   the search snippet, the Packagist description, and the social card text.

5. **Custom social preview image** (Settings → Social preview, 1280×640).

---

## 5. Before enabling GitHub Pages — read this

Only relevant if you build a docs site. These cost hours on the reference project.

- **`plugins:` in `_config.yml` REPLACES the GitHub Pages default plugin set.**
  It does not extend it. Listing only what you want silently drops
  `jekyll-readme-index`, `jekyll-titles-from-headings`, `jekyll-relative-links`
  and others. Symptom: `/dir/` 404s while `/dir/README.html` works.
- **`jekyll-readme-index` skips READMEs that have YAML front matter** — its
  documented default. Adding SEO front matter therefore breaks directory URLs
  unless you also set:
  ```yaml
  readme_index:
    with_frontmatter: true
  ```
  Both are required; neither is sufficient alone.
- **Set `url` and `baseurl` explicitly.** Pages auto-derives `baseurl` only when
  no config exists. Omitting them strips the project prefix from every canonical
  URL and sitemap entry.
- **`jekyll-sitemap` is opt-in**, not a default. `jekyll-seo-tag` already emits
  canonical/OG/JSON-LD, so structured data needs no extra work.
- **A failed Jekyll build silently serves the previous version.** No error page.
  Always check Settings → Pages for a build banner after config changes.
- **Exclude `docs/`, `wiki/`, `CLAUDE.md`, `AGENTS.md`, `GEMINI.md`,
  `SECURITY.md` and all test fixtures** before the first build.

Verification that actually catches problems:

```bash
B=https://<pages-url>
curl -s -o /dev/null -w "sitemap %{http_code}\n" $B/sitemap.xml
curl -s $B/sitemap.xml | grep -c "<loc>"          # sane count, not "everything"
curl -s $B/sitemap.xml | grep -c "README.html"    # want 0
curl -s -o /dev/null -w "a real link %{http_code}\n" $B/<some/generated/link>/   # want 200
```

---

## 6. Search Console — the one rule

Use a **URL-prefix** property. If the site lives at a subpath, **enter the full
path including the trailing slash**.

**Never use a leading slash in any Search Console input.** The field is already
prefixed with the property URL, so `/sitemap.xml` resolves to the *host root* —
a path you do not control on `github.io`. This caused two separate false
failures on the reference project (verification file, then sitemap).

Also: DNS/domain-property verification is **impossible** on `*.github.io` — you
do not own the zone. Use the HTML file or meta tag method.

If verification fails on a file that is demonstrably live, check timestamps
before re-diagnosing: GitHub's CDN caches for 600s, so a Verify click within
~10 minutes of the push can see a stale 404. Retrying is the fix.

---

## 7. Keyword strategy — where to actually compete

**Do not pursue:** `cms`, `open source cms`, `headless cms`, `laravel cms`.
Owned by vendors with orders of magnitude more authority.

**Pursue the intersection** — thin SERPs, exact product match:
`laravel mongodb cms`, `ai native cms`, `inertia react cms`,
`laravel 13 cms`, `mongodb content management laravel`,
`AI first class cms`, plus long-tail how-tos drawn from `wiki/modules`.

Validate each before writing: search it, and if page 1 is dominated by
established vendors, drop it.

---

## 8. Working conventions for this repo

- **`wiki/` is an LLM-maintained knowledge base** with its own `SCHEMA.md`.
  Read `wiki/index.md` before architectural decisions; update the relevant page
  **and append to `wiki/log.md`** after. Follow the existing frontmatter shape
  and the `> **Superseded:**` convention for corrections.
- **Do not commit.** Prepare changes and hand over the commands; the maintainer
  commits. (Confirm this still holds at session start.)
- **Do not change public repo settings** (topics, description, visibility)
  without explicit per-action approval — hand over the exact command instead.
- **Never handle credentials.** Do not accept a pasted token or write one into a
  secret store. If one is pasted, say so and advise revoking it immediately.
- **Check for generated files before editing.** On the reference project a docs
  page was regenerated and auto-committed by CI; hand edits were silently
  overwritten. Grep for the file's name in `scripts/` and `.github/workflows/`
  before touching it.

---

## 9. First session — suggested order

1. **Baseline.** Browser `site:` checks for the repo. Record numbers in
   `wiki/log.md` — without a Day-0 baseline nothing later is measurable.
2. **Competitive read.** `gh api search/repositories` for the category; find who
   owns the head terms. Be honest if a term is unwinnable.
3. **Phase 1** (§4) — topics, homepage, Packagist, README opening.
4. **Name decision** (§3) — surface it; do not decide it for the maintainer.
5. **Then stop and wait.** Indexing takes 2–4 weeks. Resist re-submitting
   sitemaps or re-requesting indexing; it changes nothing.

Useful work during the wait: directory/awesome-list submissions
(`postlight/awesome-cms`, `jamstack.org/headless-cms`, Laravel News), which is
also the only realistic route to getting the repository itself crawled.

---

## 10. Honest expectations

On the reference project, the technical work took a day; the outcome was
*indexability*, not traffic. Rankings for long-tail differentiator terms are
realistic within 90 days. **Head terms in this category are not available at any
effort level.** Anyone promising otherwise is selling something.

The largest real lever here is not on-page SEO at all — it is **Packagist
presence plus genuine community adoption**. 0 stars is the binding constraint,
and no amount of metadata fixes that.
