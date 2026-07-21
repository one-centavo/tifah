# TIFAH

Management system for pharmaceutical distributor and traceability

## Stack

- Laravel 13.8
- Laravel Breeze 2.0
- PHP 8.3
- Livewire 3.6.4
- MySQL 8.0
- Pestphp 4.7

## Comandos

- `docker compose exec app pnpm dev` -Run the server in local (This is the main command in this environment)
- `docker compose exec app php artisan test` - Run the tests (run this before commit)
- `docker compose exec app php pnpm run build` - Compile for production

## Conventions

- **Git Language:** All commit messages and name branchs MUST be written strictly in English.
- **Commits Format:** Use Conventional Commits.
    - _Correct:_ `feat(auth): add JWT validation middleware`
    - _Incorrect:_ `fix: corregido el error al iniciar sesión`

- **Code Language:** All the code (variables names, functions, class, data bases, routes, tables and technical documentation) must be written exclusively in **English**.

- **Self-documenting code:** The code must be clean and easy to read on its own. Only use comments if there is a complex architectural decision or a technical hack that requires contextual explanation.

- **Tailwind rules:** Convenctions with good practice for TailwindCSS
    - Avoid using arbitrary values (e.g., bg-[FFFF]), preferably, use utility classes, look for a class that resembles the desired one.
    - If you don't find a similar utility class, add in `resources/css/app.css` to reuse the class

## Do Not Do

- Do not make a single giant commit at the end of a task; keep your commits atomic.
- Do not use past-tense verbs in commit messages (e.g., do not use `added`, use `add`).
- Do not mix code refactoring with new logic in the same commit.
- **Do not comment on the obvious:** Writing comments that merely repeat what the code already does is strictly prohibited (e.g., do not write `// Inicializa el usuario` right above `const user = new User()`). If the code is clear, delete the comment.
- **Do not mix languages (Spanglish):** Do not use variable names in Spanish or mixtures like `getUsuarios()` or `savedData_updated`. All backend logic and naming must be strictly in English.

## Workflow

- Before starting a non-trivial task, propose a plan and wait for my OK.
- One task at a time; when finished, tell me what you changed so I can review it.
- If you are not at least 80% sure, ask. Do not make things up.
- **Git Flow & Branches:** Always work on independent feature branches (`feature/feature-name` or `bugfix/bug-name`) created from `dev`. Never commit directly to `main` or `dev`.
- **Atomic Commits (Mandatory):** Make a commit for each logical change or completed subtask. A commit must contain a single unit of work (e.g., create the migration, then another commit for the model, and another for the controller). Do not accumulate changes from different files or purposes into a single commit.
- **Pre-validation:** Before every commit, run `[comando test]` and `[comando lint]`. If they fail, do not make the commit.
