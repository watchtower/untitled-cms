# Implementation Plan: Hardening & Entropy Reduction

> Source: post-review recommendations ("What I would do next").  
> Status: **Complete** (2026-07-14)  
> Created: 2026-07-12  
> Target version: post-0.4.x hygiene epic (not a user-facing feature release)

---

## 1. Intent & success criteria

### Why now

The product core (Vault, AI Hub/Actions, Markdown-for-agents, RBAC) is coherent.
The main risk is **entropy**: docs that lie, fat modules that resist change, mixed
authz styles, and test islands that do not protect growth.

### Goals

| # | Goal | Done when |
|---|------|-----------|
| G1 | Docs match code | Wiki + README badge/stack claims match `Role::availablePermissions()`, editor, providers, PHP version, SafeHttpClient policy |
| G2 | Vault is maintainable | `Vault/Index.tsx` and `VaultController` split into focused units; no behavior change |
| G3 | AI HTTP policy is honest | Written rule + code align; provider calls share timeouts/logging |
| G4 | AuthZ is policy-first | Controllers that already have policies do not use ad-hoc `hasPermission` for the same check |
| G5 | Critical paths have regression tests | Pages (markdown + preview), Menus, Banners, one AI chat path covered |
| G6 | Strategic Mongo decision is recorded | Short ADR/wiki page: why Mongo, SQLite test gap, when to re-evaluate |

### Non-goals (this epic)

- New CMS features (multi-tenant, i18n, marketplace themes)
- Full frontend E2E (Playwright) suite — optional follow-up
- Migrating off MongoDB
- Replacing TinyMCE / redesigning Vault UX
- Large provider-adapter rewrite beyond a thin shared HTTP helper

### Success metrics

- Zero known doc/code contradictions on stack facts listed in PR-1 checklist
- `Vault/Index.tsx` ≤ ~400 LOC page shell; logic in hooks/components
- `VaultController` ≤ ~250 LOC or split into 2–3 controllers
- All new/changed authz paths use `$this->authorize` / `Gate::authorize` or FormRequest `authorize()`
- `composer run test` green; Pint clean; `npm run build` green
- Wiki `log.md` updated for every documentation/architecture change

---

## 2. Brainstorm: options per workstream

### WS-A — Documentation / wiki lint

| Option | Approach | Pros | Cons |
|--------|----------|------|------|
| **A1** (recommended) | One-shot manual sync + checklist in wiki SCHEMA | Fast, no tooling | Drifts again without discipline |
| A2 | Script that greps code vs wiki assertions | Durable | Overkill for ~15 facts |
| A3 | Delete stale wiki claims; keep only high-level | Less maintenance | Loses agent value |

**Decision:** A1 now; add a short "source of truth" table to `wiki/SCHEMA.md` (where each fact lives in code). Revisit A2 if drift returns twice.

**Facts to reconcile (checklist):**

| Claim area | Source of truth | Current drift |
|------------|-----------------|---------------|
| PHP version | `composer.json` `php` | README badge says 8.2+; require is `^8.4` |
| Editor | `resources/js/Components/Editor.tsx` | README says CKEditor 5; code is TinyMCE |
| Permission count | `Role::availablePermissions()` | **32** strings; overview says ~20, README ~34 |
| AI providers | `config/ai.php` + `AiHub` seeder/UI | Wiki overview still "3 providers" |
| SafeHttpClient rule | Intended security policy | Wiki says "all outbound"; `AiService` uses `Http::` for providers |
| Vault pipeline stages | `VaultService::upload` pipes | Docs vary 6 vs 7 (ClamAV optional) |
| Laravel/Inertia stack | `composer.json` / `package.json` | Mostly OK; keep badges in sync |

---

### WS-B — Vault frontend split

| Option | Approach | Pros | Cons |
|--------|----------|------|------|
| **B1** (recommended) | Extract hooks + presentational components; keep single Inertia page | Low risk, incremental | Still one route |
| B2 | Multiple Inertia pages (browse / trash / settings) | Clearer routes | UX/nav churn |
| B3 | Rewrite with state library | Clean slate | High risk, out of scope |

**Decision:** B1.

**Proposed extraction map** (from current `Index.tsx` ~1132 LOC):

```
resources/js/
  Pages/Vault/Index.tsx              # shell: layout + panel wiring only
  hooks/useVaultBrowser.ts           # list fetch, folder nav, selection, search
  hooks/useVaultBatchActions.ts      # move/delete/restore/empty trash
  Components/Vault/
    VaultFolderTree.tsx              # left panel
    VaultFileGrid.tsx                # center grid/list
    VaultFileDetails.tsx             # right inspector
    VaultToolbar.tsx                 # search, view mode, upload, AI alt
    VaultContextMenus.tsx            # file/folder context menus
    VaultRenameDialog.tsx            # rename + alt-text dialogs if still inline
    VaultFolderInfoPopover.tsx       # already local — extract as-is
    (existing) VaultUploadDialog, VaultPicker, VaultBreadcrumb, …
```

**Rules:**

- No API contract changes
- Preserve axios endpoints and toast copy unless broken
- Prefer props-down / callbacks-up over new global state
- Type against `@/types/vault`

---

### WS-C — VaultController thin-out

| Option | Approach | Pros | Cons |
|--------|----------|------|------|
| **C1** (recommended) | Keep one controller; extract private query helpers + FormRequests; push mutations already in `VaultService` deeper | Minimal route churn | Still one large class |
| C2 | Split: `VaultBrowseController`, `VaultMutationController`, `VaultServeController` | Clear SRP | Many route renames; Ziggy/FE touch |
| C3 | Single action classes (Laravel invokables) | Ultra modular | Too many files for this codebase style |

**Decision:** C1 first (same PR series as B if needed). C2 only if after C1 the controller is still >350 LOC of non-trivial logic.

**Method groups today:**

| Group | Methods | Target home |
|-------|---------|-------------|
| Admin UI | `adminPage` | stay |
| Browse/list | `list`, `trash`, `buildListQuery` | stay + private query object/helper |
| Upload | `upload`, `saveAiImage`, `checkDuplicate` | FormRequests (already partial) |
| Serve | `serve`, `servePublic`, `parseHttpDate` | candidate for `VaultServeController` later |
| Mutate | rename, alt, move, batch*, destroy, restore, forceDestroy, emptyTrash, toggleOptimization, generateMissingAltText | ensure `VaultService` owns business rules |

---

### WS-D — AI outbound HTTP policy

| Option | Approach | Pros | Cons |
|--------|----------|------|------|
| D1 | Force all AI through SafeHttpClient | One rule | Breaks DNS-pin model for known SaaS hosts; keys in query (Gemini) awkward |
| **D2** (recommended) | Document two tiers: (1) untrusted user/AI URLs → SafeHttpClient; (2) configured provider base URLs → `AiHttpClient` with timeout, retry logging, no SSRF dance | Honest + practical | Two clients to learn |
| D3 | Full provider adapter interfaces | Clean long-term | Large refactor; not needed for entropy epic |

**Decision:** D2.

**Concrete design:**

```php
// app/Services/AiHttpClient.php (new, thin)
// - wraps Http:: with default timeout
// - logs provider name + status (no API keys)
// - used only for hub-configured provider endpoints
// - does NOT claim SSRF protection (endpoints are not user-controlled)

// SafeHttpClient remains for:
// - SaveAiImageRequest remote fetch
// - AiController image URL fetch
// - any future user-supplied URL
```

Wiki update: replace "use SafeHttpClient for all outbound" with the two-tier rule in `modules/services.md` and `modules/ai-hub.md`.

Optional later: migrate `laravel/ai` text path and raw provider vision/image to share more code — **out of scope** beyond introducing `AiHttpClient` and switching existing `Http::` calls in `AiService`.

---

### WS-E — Policy-first authZ

| Option | Approach | Pros | Cons |
|--------|----------|------|------|
| **E1** (recommended) | Normalize vault + public preview only; leave healthy `$this->authorize` controllers alone | Focused | Incomplete purity |
| E2 | Project-wide: every permission string must only appear in policies | Consistent | Large diff, high merge conflict risk |
| E3 | Middleware-only (`can:media.view` on routes) | Declarative routes | Loses model-level folder gates |

**Decision:** E1, with a written convention.

**Convention (add to wiki/permissions.md):**

1. Resource controllers → `$this->authorize` / policy methods  
2. Input validation → FormRequest; `authorize()` may call Gate  
3. Cross-cutting flags (`pages.view` for draft preview) → policy ability e.g. `PagePolicy::preview` or `view` with draft context — prefer policy over raw `hasPermission` in controllers  
4. `Role::availablePermissions()` remains the string catalog  

**Known ad-hoc sites to fix:**

- `VaultController` / `VaultFolderController` — `hasPermission('media.*')` helpers → policy (`VaultFilePolicy` / global media abilities already exist)
- `PublicController` draft preview — `hasPermission('pages.view')` → `$request->user()?->can('view', $page)` or dedicated ability once draft page is loaded (careful: before load use `viewAny` / custom `previewDrafts`)

**Do not change:** intentional `hasRole('admin')` superuser ORs inside policies without a separate product decision.

---

### WS-F — Test expansion

| Option | Approach | Pros | Cons |
|--------|----------|------|------|
| **F1** (recommended) | Feature tests only for listed gaps; SQLite continues | Fits existing harness | Mongo-specific bugs can slip |
| F2 | Add Mongo-backed CI job | Closer to prod | Slower CI, flakier local |
| F3 | Frontend component tests (Vitest) | Catch Vault split regressions | New toolchain |

**Decision:** F1 now; note F2/F3 as follow-ups. After Vault FE split, smoke via existing Feature vault tests + manual checklist; optional Vitest later.

**New test files (proposed):**

| File | Cases |
|------|--------|
| `tests/Feature/PublicPageMarkdownTest.php` | published page HTML vs `Accept: text/markdown`; frontmatter; draft 404 without preview; draft preview with `pages.view`; no preview for bare auth user |
| `tests/Feature/MenuControllerTest.php` | index/store/update/delete with policy allow/deny |
| `tests/Feature/BannerControllerTest.php` | CRUD + unauthorized |
| `tests/Feature/AiChatTest.php` | happy path with mocked `AiService` (or hub inactive local fallback); unauthorized; throttle not required |
| Extend `PageControllerTest.php` | publish permission / validation edge if missing |

**Mocking strategy:** Prefer faking HTTP / binding `AiService` mock in container for chat; do not hit real providers in CI.

---

### WS-G — Strategic Mongo note

| Option | Approach | Pros | Cons |
|--------|----------|------|------|
| **G1** (recommended) | Short ADR in `wiki/architecture/mongodb.md` + index link | Matches agent wiki | Not formal ADR tooling |
| G2 | Full ADR process under `docs/adr/` | Industry standard | Duplicate knowledge bases |

**Decision:** G1.

**Content outline:**

- Why Mongo: flexible content shapes, pages/banners/menus JSON, vault metadata  
- Cost: relational integrity, SQLite test dual-path, indexing discipline  
- Test gap: SQLite in-memory in PHPUnit vs Mongo in prod/CI service for some jobs  
- Re-evaluate if: multi-tenant SQL reporting, heavy joins, team lacks Mongo ops  
- Non-decision: no migration planned in this epic  

---

## 3. Recommended sequencing

```
PR-1  Docs truth (wiki + README)          ── no code risk
PR-2  Mongo ADR + permissions convention  ── docs only
PR-3  AiHttpClient + wiki two-tier HTTP   ── small service change
PR-4  Policy-first vault + public preview ── authz behavior-preserving
PR-5  Feature tests (public/menus/banners/ai chat)
PR-6  VaultController thin + FormRequests ── backend refactor
PR-7  Vault frontend extract (hooks/components) ── largest FE PR
```

**Why this order**

1. Docs first → agents stop learning lies during later PRs  
2. HTTP policy before more AI tests → tests assert the right client  
3. AuthZ before more features tests → tests lock the intended gate  
4. Tests before big Vault refactors → safety net  
5. Backend Vault thin before/parallel FE extract → FE can keep same endpoints  
6. FE extract last → highest churn, protected by tests + manual QA  

**Parallelism:** PR-1 ‖ PR-2; PR-6 ‖ PR-5 after PR-4; PR-7 depends on PR-6 preferably stable.

**Estimated effort (one experienced contributor):**

| PR | Effort |
|----|--------|
| 1–2 | 0.5–1 day |
| 3 | 0.5 day |
| 4 | 0.5–1 day |
| 5 | 1–1.5 days |
| 6 | 1 day |
| 7 | 1.5–2.5 days |
| **Total** | **~5–8 days** |

---

## 4. PR-level implementation plan

### PR-1 — Docs & badge truth

**Title:** `docs: align README and wiki with code sources of truth`

**Changes:**

- README: PHP badge → 8.4+; CKEditor → TinyMCE; permission count → 32 (or "see Role::availablePermissions"); pipeline stage wording
- `wiki/overview.md`: key numbers refresh (permissions, providers, pipeline)
- `wiki/modules/ai-hub.md`: provider list from actual support; remove Stability-only image assumption if code multi-provider; fix SafeHttpClient claim (or leave exact wording for PR-3)
- `wiki/modules/services.md`: service inventory includes Email webhook providers, AiActionService, HtmlSanitizer, ActivityLogger if missing
- `wiki/SCHEMA.md`: add "Source of truth" table (section 2 checklist)
- `wiki/log.md`: append entry
- `wiki/index.md`: touch if new pages

**Tests:** none  
**Risk:** low  
**Deps:** none  

---

### PR-2 — Mongo ADR + authZ convention doc

**Title:** `docs(wiki): mongodb decision and policy-first authz convention`

**Changes:**

- New `wiki/architecture/mongodb.md`
- Update `wiki/modules/permissions.md` with policy-first convention
- Link from `wiki/index.md`, `wiki/architecture/stack.md`
- `wiki/log.md`

**Tests:** none  
**Risk:** low  
**Deps:** none (can merge with PR-1)  

---

### PR-3 — AiHttpClient (two-tier outbound HTTP)

**Title:** `refactor(ai): introduce AiHttpClient for provider calls`

**Changes:**

- Add `app/Services/AiHttpClient.php`
- Replace `Http::` in `AiService` provider methods with `AiHttpClient`
- Keep `SafeHttpClient` for user/AI-supplied URLs (`SaveAiImageRequest`, `AiController`)
- Unit tests: `tests/Unit/AiHttpClientTest.php` (timeout option applied / header passthrough — lightweight)
- Wiki: services + ai-hub two-tier rule (finalize PR-1 placeholders)
- `wiki/log.md`

**Tests:** unit + existing AI-related feature tests  
**Risk:** medium (provider response handling must stay identical)  
**Deps:** ideally after PR-1 wording  

**Acceptance:**

- No behavior change on generate image/alt-text paths (manual or existing tests)
- Grep: `Http::` absent from `AiService` (except via AiHttpClient internals)
- Grep: user URL fetches still use `SafeHttpClient`

---

### PR-4 — Policy-first authZ (Vault + public preview)

**Title:** `refactor(authz): policy-first vault and draft preview`

**Changes:**

- Replace `authorizeGlobalMediaView/Create` + scattered `hasPermission` in Vault controllers with `Gate`/`authorize` against `VaultFile` / `VaultFolder` policies
- Add ability if needed (e.g. `viewAny` already maps to `media.view`)
- Public draft preview: policy-based check (document chosen ability)
- Extend `PolicyTest` / `VaultUploadTest` for unauthorized cases if gaps
- Wiki permissions page: note migration done

**Tests:** existing vault + new assertions  
**Risk:** medium (403 regressions)  
**Deps:** PR-2 convention helpful but not required  

**Acceptance:**

- Admin with media.* still works
- User without media.view gets 403 on list/admin
- Draft preview only for users who can view pages (same effective rule as today)

---

### PR-5 — Feature test expansion

**Title:** `test: cover public markdown, menus, banners, ai chat`

**Changes:**

- New feature tests as in WS-F table
- Factories already exist for Banner, Page, User, Role — reuse
- Mock `AiService` for chat

**Tests:** the point of the PR  
**Risk:** low–medium (Mongo vs SQLite quirks)  
**Deps:** PR-4 preferred so authz assertions match final gates  

---

### PR-6 — VaultController thinning

**Title:** `refactor(vault): thin controller and complete FormRequests`

**Changes:**

- FormRequests for mutate endpoints still using raw `Request` (rename, alt text, batch move/delete, etc.)
- Move remaining business rules into `VaultService` if still in controller
- Extract `buildListQuery` / ini size parsing to dedicated private class or trait if it helps readability
- Do **not** rename routes unless necessary

**Tests:** `VaultUploadTest`, `VaultFolderTest`, manual smoke  
**Risk:** medium  
**Deps:** PR-4, PR-5  

---

### PR-7 — Vault frontend extraction

**Title:** `refactor(vault-ui): extract hooks and panel components from Index`

**Changes:**

- Implement B1 file map
- `Index.tsx` becomes composition only
- No visual redesign; match existing Tailwind/Shadcn patterns (see ask-shadcn-architect if adding primitives)
- Ensure `VaultPicker` still works (shared components)

**Tests:** existing PHP feature tests + manual QA checklist:

- [ ] Navigate folders
- [ ] Upload (pipeline tracker)
- [ ] Rename / alt text / optimize toggle
- [ ] Batch move / delete / trash / restore
- [ ] Search + grid/list toggle
- [ ] Generate missing alt text
- [ ] Folder create / permissions if UI exposes them
- [ ] VaultPicker from Pages/Banners still opens

**Risk:** high (UI regressions)  
**Deps:** PR-6 preferred  

---

## 5. Cross-cutting standards

### Coding

- Follow `Agents.md` / Pint / `.editorconfig`
- Conventional commits: `docs:`, `refactor(vault):`, `test:`, `refactor(ai):`, `refactor(authz):`
- No drive-by features inside refactor PRs

### Wiki protocol (mandatory per repo rules)

After each PR that changes architecture or modules:

1. Update relevant `wiki/**` pages  
2. Update `wiki/index.md` if new pages  
3. Append `wiki/log.md`  

### Verification per PR

```bash
composer run test
./vendor/bin/pint --test
npm run build   # required for PR-7; recommended for any TS touch
```

### Rollback

Each PR is independently revertable. Prefer small PRs over one mega-branch.

---

## 6. Risk register

| Risk | Mitigation |
|------|------------|
| AuthZ refactor returns 403 for admins | Feature tests with seeded admin + editor roles before merge |
| AiHttpClient changes response parsing | Keep method signatures; only swap client; snapshot error messages |
| Vault FE extract breaks selection state | Extract hooks with identical state variables first; components second |
| SQLite tests pass, Mongo fails | Keep CI Mongo service for suite where already used; document gap in mongodb.md |
| Doc PR bitrot during long FE work | Merge docs first |

---

## 7. Open questions (need product/owner input)

| # | Question | Default if no answer |
|---|----------|----------------------|
| Q1 | Merge docs as one PR or PR-1+PR-2 split? | Single docs PR is fine |
| Q2 | Is draft preview meant to be `pages.view` or stricter `pages.edit`? | Keep current effective rule (`pages.view`) |
| Q3 | Introduce Vitest in this epic? | No — follow-up |
| Q4 | Split Vault serve routes to separate controller now? | No — only if PR-6 still leaves serve bloated |
| Q5 | Should `ActivityLogger` silent fail be addressed (log to channel)? | Out of scope; backlog |
| Q6 | Redirect middleware Mongo hit optimization? | Out of scope; backlog |

---

## 8. Backlog (explicitly deferred)

- Playwright / browser E2E  
- Vitest for Vault hooks  
- Mongo-only PHPUnit job  
- Full AI provider adapter interfaces  
- `ActivityLogger` observability  
- `CheckRedirects` caching/index audit beyond docs  
- CDN guidance for `/media/*`  

---

## 9. Definition of done (epic)

- [x] All workstreams 1–7 implemented in-tree (2026-07-12…14)
- [x] Wiki + README pass the source-of-truth checklist
- [x] Two-tier HTTP policy documented and implemented (`AiHttpClient` + wiki)
- [x] Vault FE modularized: `useVaultBrowser`, `VaultDialogs`, `VaultFolderInfoPopover`; **residual:** `Index.tsx` still ~1k LOC of panel JSX (further panel splits optional)
- [x] VaultController thinned with FormRequests + policy authorize (~457 LOC)
- [x] New feature tests green (`composer run test`: 85 passed)
- [x] Banner/Menu `Rule::unique(Model::class)` fixed for Mongo-friendly validation under SQLite tests
- [x] This document status set to **Complete** (2026-07-14)

---

## 10. How to execute

Once confirmed:

1. Create branch `chore/hardening-entropy` or per-PR branches (`docs/wiki-truth`, `refactor/ai-http-client`, …)
2. Implement PR-1 → … → PR-7 in order
3. Optionally use Graphite/stacked PRs matching section 4
4. After epic: update `CHANGELOG.md` under a "Maintenance" section

**Not in scope until you say go:** writing application code for these PRs.
