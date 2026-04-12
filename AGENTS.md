# Repository Guidelines

## Project Structure & Module Organization
This repository is a Laravel 13 CMS with a React + Inertia admin UI. Backend code lives in `app/`, routes in `routes/`, database migrations and seeders in `database/`, and tests in `tests/Feature`. Frontend code is under `resources/js/` and `resources/css/`, with pages in `resources/js/Pages`, shared UI in `resources/js/Components`, and layouts in `resources/js/Layouts`. Project notes and architecture docs live in `wiki/` and `docs/`, with module-specific pages such as `wiki/modules/vault.md` and `wiki/architecture/request-flow.md`.

## LLM Wiki & Knowledge Base Management
A persistent, LLM-maintained knowledge base lives in the `wiki/` directory. This is the single source of truth for architectural context and module guidelines.

**CRITICAL INSTRUCTION FOR ALL AGENTS:** 
You **MUST proactively construct and update the `wiki/`** immediately whenever you introduce new features, database schemas, API integrations, or architectural changes. Do not leave the system in an undocumented state; knowledge MUST be persisted here across operational runs.

### Automatic Retrieval Protocol
To effectively reference this knowledge in future runs and avoid redundant analysis:
1. **Initialize**: Read `wiki/index.md` at the start of complex requests to map out the current structure and available documentation.
2. **Navigate**: Use file tools to access detailed breakdowns under `wiki/architecture/`, `wiki/database/`, `wiki/frontend/`, and `wiki/modules/`.
3. **Comply**: Read `wiki/SCHEMA.md` to understand formatting rules, update conventions, and query mechanics.
4. **Log**: Update the `wiki/log.md` with a timestamped note after making documented adjustments.

## Build, Test, and Development Commands
- `composer run dev`: starts the Laravel server, queue listener, log viewer, and Vite HMR together.
- `npm run dev`: runs the Vite dev server for frontend assets.
- `npm run build`: compiles TypeScript and production assets.
- `composer run setup`: installs dependencies, prepares `.env`, runs migrations and seeders, and builds assets.
- `composer run test`: clears config and runs the PHPUnit suite.
- `./vendor/bin/pint`: formats PHP code using Laravel Pint.

## Coding Style & Naming Conventions
Follow `.editorconfig`: UTF-8, LF endings, 4-space indentation, and no trailing whitespace. Use `2` spaces in YAML files. PHP code should follow Laravel conventions and be kept Pint-clean. React/TypeScript files use PascalCase for components, camelCase for functions and variables, and descriptive names that match the feature area, such as `resources/js/Pages/Vault/Index.tsx`.

## Testing Guidelines
PHPUnit is configured in `phpunit.xml`; tests live in `tests/Feature` and should use descriptive names like `VaultFolderTest.php` or `AuthenticationTest.php`. Prefer feature tests for controller, policy, and workflow coverage. Run the full suite with `composer run test`; use `php artisan test --filter NameOfTest` when iterating on one case.

## Commit & Pull Request Guidelines
Recent commits use conventional-style prefixes with optional scopes, for example `fix(tests): ...`, `feat(vault): ...`, or `style: ...`. Keep commit subjects short and specific. Pull requests should describe the change, list any migration or seeding steps, and include screenshots for UI work. Link related issues when applicable and note any test commands you ran.

## Security & Configuration Tips
Do not commit secrets or environment-specific values. Local setup expects `.env`, MongoDB credentials, and a valid app key. If you change upload, auth, or AI-related code, call out any new permissions, queue jobs, or environment variables in the PR notes.
