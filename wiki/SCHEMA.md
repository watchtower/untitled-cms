# Wiki Schema

This file governs how the LLM maintains the untitled-cms project wiki.
Read this before any wiki operation.

## Purpose

This wiki is a persistent, compounding knowledge base for the untitled-cms codebase.
It captures architecture decisions, patterns, gotchas, and evolving understanding that
is not easily derived from reading the code alone. It is **not** a substitute for the
code — it is a layer above it that explains the *why*, the *how things connect*, and
the *things to watch out for*.

## Directory layout

```
wiki/
  SCHEMA.md              ← this file
  index.md               ← content catalog (update on every ingest)
  log.md                 ← append-only operation log
  overview.md            ← project summary and orientation
  architecture/
    stack.md             ← technology choices and key design decisions
    request-flow.md      ← how a request moves through the system
    middleware.md        ← middleware stack
    testing.md           ← test setup and gotchas
  database/
    collections.md       ← MongoDB models, collections, conventions
  frontend/
    ui-stack.md          ← React/Inertia patterns and libraries
  modules/
    services.md          ← app/Services/* overview
    vault.md             ← Vault upload pipeline
    permissions.md       ← role-based access control
    ai-hub.md            ← AI provider config and patterns
```

New pages go into the most fitting subfolder. When adding a page, also add it to `index.md`.

## Page format

Every wiki page starts with:

```markdown
# Page Title

> One-line summary (used in index.md)

Last updated: YYYY-MM-DD
```

Then free-form markdown. Use `[[folder/page]]` wiki-link syntax for cross-references
(Obsidian-compatible). Always link to related pages at the bottom under `## See also`.

## Operations

### Ingest
When given a source to process (file, PR, chat summary, ADR, etc.):
1. Read the source carefully.
2. Discuss key takeaways with the user if helpful.
3. Write or update the relevant wiki pages.
4. Update `index.md` if new pages were created.
5. Append an entry to `log.md`: `## [YYYY-MM-DD] ingest | <title>`

### Query
When asked a question about the project:
1. Read `index.md` to find relevant pages.
2. Read those pages.
3. Synthesize an answer with citations (link to wiki pages).
4. If the answer is worth keeping, file it as a new wiki page and log it:
   `## [YYYY-MM-DD] query | <question summary>`

### Update
When code changes and wiki content is stale:
1. Update affected pages.
2. Log: `## [YYYY-MM-DD] update | <what changed>`

### Lint
Periodically health-check the wiki:
- Flag contradictions between pages.
- Flag stale claims superseded by recent changes.
- Flag orphan pages (no inbound links).
- Flag concepts mentioned but lacking their own page.
- Suggest new questions to investigate.
- Log: `## [YYYY-MM-DD] lint | <summary of findings>`

## Conventions

- Keep pages focused. One concept per page is better than one mega-page.
- Prefer short summaries at the top, detail below.
- When something is surprising or non-obvious, call it out explicitly.
- Mark uncertainty: use "unclear", "TBD", or "investigate" rather than stating guesses as facts.
- Do not duplicate what is already in CLAUDE.md — link to it instead or build on it.
