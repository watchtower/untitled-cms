# UI Stack

> React 19 + TypeScript + Inertia.js admin SPA patterns and conventions.

Last updated: 2026-05-23

## Stack

- **React 19 + TypeScript** — component framework
- **Inertia.js** — props-based routing (no client-side router, no API layer)
- **Tailwind CSS v4** — utility-first styling with native CSS `@theme` and OKLCH variables
- **Shadcn/Radix UI** — accessible component primitives using unified `radix-ui` dependency
- **Vite 7** — build tool with HMR

## Key libraries by concern

| Concern | Library |
|---------|---------|
| Data grids | TanStack Table |
| Drag and drop | @dnd-kit (banners, vault) |
| Charts | Recharts (dashboard analytics) |
| Form validation | Zod |
| Toast notifications | Sonner |

## Page structure

Each page in `resources/js/Pages/` corresponds to a controller. Inertia renders
the matching page component and passes controller data as props. There are no
separate API calls — all data arrives with the page load.

```
resources/js/Pages/
  Auth/
  Dashboard.tsx
  Pages/         ← content pages management
  Vault/         ← media manager
  Users/
  Roles/
  Banners/
  Menus/
  AiHub/
  Settings/
  ...
```

## Shared props (always available)

Injected by `HandleInertiaRequests` middleware:
- `auth.user` — current user object
- `permissions` — array of permission strings for the current user
- `menus` — navigation menu data
- `settings` — site-wide settings key/value

## Inertia patterns

- Use `useForm()` from `@inertiajs/react` for forms — handles loading state,
  errors, and submission automatically.
- Use `router.visit()` or `<Link>` for navigation — never `window.location`.
- Props are typed — check the corresponding controller's `Inertia::render()` call
  to see what's available on a given page.

## No API layer

There is no separate REST/GraphQL API consumed by the frontend. If you need data
that isn't in the initial page props, add it to the controller's props or use a
partial Inertia reload — not a fetch call.

## See also

- [[architecture/request-flow]] — how Inertia fits in the request flow
- [[architecture/middleware]] — HandleInertiaRequests and shared props
