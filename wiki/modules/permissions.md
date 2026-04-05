# Permissions

> Role-based access control with policy classes and per-user caching.

Last updated: 2026-04-05

## Format

Permissions are strings in `resource.action` format:

```
pages.edit
media.upload
users.delete
```

The full set of ~20 permissions is defined in `app/Traits/HasRoles.php`.

## Components

### HasRoles trait
Applied to the `User` model. Defines the canonical permission list, provides
`hasPermission()` / `can()` helpers, and handles permission caching with a
configurable TTL.

### 8 Policy classes (`app/Policies/`)
One per resource type. Policies use `hasPermission()` under the hood.
Laravel's policy system routes `$user->can('edit', $page)` to the right
policy method automatically.

### CheckPermission middleware
The `can` middleware alias points to this custom class, **not** Laravel's
built-in `can` middleware. Behaviour is similar but enforces the project's
permission string format. Be aware of this if debugging auth issues — the
standard Laravel docs may not apply exactly.

## Caching

Permissions are cached per user in the `HasRoles` trait. If you change a user's
role in the database directly (bypassing the app), their cached permissions will
be stale until the TTL expires. Use the app's role management UI to avoid this.

## Gotcha: custom middleware alias

If you add a new route and use `->middleware('can:resource.action')`, make sure
you understand it hits `CheckPermission`, not Laravel's default. If something
isn't working as expected, check where middleware aliases are registered to
confirm the mapping.

## See also

- [[architecture/middleware]] — full middleware stack
- [[architecture/request-flow]] — where auth fits in the request lifecycle
