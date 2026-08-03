# Tech Stack and Conventions

## Technologies

- **Backend:** PHP 8.3 / Laravel 13.8 (Laravel Breeze 2.0)
- **Frontend / Reactive UI:** Livewire 3.6.4 / Volt 1.7.0 (TALL Stack, Alpine.js 3.x)
- **Styling:** TailwindCSS v4.0 (CSS-first approach, entry point: `resources/css/app.css`, no `tailwind.config.js`)
- **Database:** MySQL 8.0
- **Testing:** Pestphp 4.7
- **Package Manager / Bundler:** PNPM v10.33.2 / Vite

## Key Files and Modules

- `app/services/` — Contains the business logic, complex validations, and SOFIA PLUS Excel imports (keeping controllers lean).
- `resources/views/livewire/` — Contains the Livewire Single-file Components (SFC), acting as both controllers and views.
- `database/migrations/` — Database schema source of truth (strictly `snake_case`).

## Commands

- `docker compose exec app pnpm dev` — Run the local development server (main command).
- `docker compose exec app php artisan test` — Run the test suite.
- `docker compose exec app php vendor/bin/pint` — Format code according to PSR-12 standard (Pint).
- `docker compose exec app php vendor/bin/phpstan analyse` — Perform static analysis on the codebase (PHPStan).
- `docker compose exec app php pnpm run build` — Compile assets for production.

## Data Model and Domain

_The core entities, database structures, and business rules. Document only the non-obvious invariants, special mechanics, or fields that control critical behavior._

- Database tables and column names must be written strictly in `snake_case` and in English.

## Conventions

- **Programming Language Style:** Standard PSR-12. Class names must use `PascalCase`, while methods and variables must use `camelCase`. Naming must be self-explanatory.
- **Git Language & Flow:** All commit messages and branch names MUST be written strictly in English. Always work on independent feature branches (`feature/feature-name` or `bugfix/bug-name`) branched off from `dev`. Never commit directly to `main` or `dev`.
- **Commit Format:** Use Conventional Commits in the imperative present tense (e.g., `feat(auth): add JWT validation middleware`, NOT `fix: corregido el error al iniciar sesión` or `feat(auth): added JWT validation middleware`).
- **Code Language:** All code (variables, functions, classes, databases, routes, tables, and technical documentation) must be written exclusively in **English**.
- **User Interface Language:** User-facing text, labels, and messages must be written in **Spanish (Colombia)**.
- **Self-documenting Code:** Code must be clean and self-explanatory. Comments are allowed only for complex architectural decisions or technical hacks that require context.
- **Tailwind Rules:** Avoid using arbitrary values (e.g., `bg-[#FFFF]`). Prefer standard utility classes. If a custom style is required, define it in `resources/css/app.css` to enable reuse.

## Hard Limits (Do Not Do)

- **No monolithic commits:** Do not make a single giant commit at the end of a task; keep commits atomic and focused on a single unit of work (e.g., separate commits for migration, model, and controllers).
- **No past-tense verbs:** Never use past-tense verbs in commit messages (e.g., do not use `added` or `fixed`; use `add` or `fix`).
- **No mixed commits:** Do not mix code refactoring with new logic in the same commit.
- **No obvious comments:** Do not write comments that merely repeat what the code does (e.g., `// Initialize user` above `const user = new User()`). If the code is clear, remove the comment.
- **No mixed languages (Spanglish):** Do not mix English and Spanish in variable or function names (e.g., do not use `getUsuarios()` or `savedData_updated`).
- **No pre-validation bypass:** Do not commit code without running the test suite and lint/formatting checks first. If tests or lint check fails, do not commit.
