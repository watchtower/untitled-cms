# Request Flow

> How a request moves from browser to response.

Last updated: 2026-04-05

## Flow

```
Browser → Laravel Route → Controller → Service/Model → MongoDB
                                    ↓
                           Inertia Response → React Page Component
```

Inertia.js is the bridge: controllers return `Inertia::render('PageName', $props)`
rather than JSON or HTML templates. Props flow directly into React components.
There is **no client-side router** — navigation is server-driven.

## Inertia shared props

`HandleInertiaRequests` middleware injects these on every page load:
- `auth` — current user
- `permissions` — current user's permission set (cached)
- `menus` — navigation menu data
- `settings` — site-wide settings key/value

## Route groups

- **Public (no auth):** `/`, `/feed`, `/sitemap.md`, `/{slug}`
- **Authenticated** (middleware: `auth`, `verified`): all admin routes
- **AI endpoints:** rate-limited (30/min text, 10/min image)

## Dual content format

Public routes respond with HTML by default. When `Accept: text/markdown` is sent
(AI crawlers/agents), they respond with Markdown + YAML frontmatter instead.
This is the "AI-native" aspect of the CMS — no HTML parsing required for agents.

## See also

- [[architecture/middleware]] — middleware stack that wraps every request
- [[architecture/stack]] — technology choices
- [[frontend/ui-stack]] — what happens on the React side
