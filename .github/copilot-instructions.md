## 1. High-Level Details

- **Summary:** The TIFAH project is a comprehensive inventory management system for pharmaceutical distribution. It allows Administrators and Warehouse Assistants to manage master data (medicines, categories, suppliers, and regulatory records), control inventory through lot registration (including expiration dates and costs), handle sales and invoicing with automatic stock deduction, and generate administrative reports (Kardex, sales, and inventory valuation).
- **Project Type:** Functional web application prototype (Full-stack Monolithic).

### Core Stack (Languages & Frameworks)

- **Backend:** PHP v8.3+ (Laravel v13.x).
- **Frontend:** Livewire v4.2.4, Alpine.js v3.x (TALL Stack).
- **Styling:** TailwindCSS v4.0 (CSS-first configuration, no tailwind.config.js file).
- **Build Tool:** Vite v8.0.

### Environment & Runtimes (Target Runtimes)

- **Runtime:** Node.js v20.2.
- **Package Managers:** PNPM v10.33.2 (Frontend) and Composer v5.0 (Backend).
- **Infrastructure:** Docker v29.1+ (Isolated containers - Laravel Sail).
- **Services:** Nginx v1.29.8 (Web Server), MySQL v8.0 (Database).
- **Host OS:** Linux Mint 22.3 (Ubuntu/Debian based).

## 2. Build and Validation Instructions

### Bootstrap:

> **Steps to set up the development environment from scratch:**

1. **Environment:** `cp .env.example .env && cp docker-compose.override.example.yml docker-compose.override.yml` (Manually configure database credentials).
2. **Infrastructure:** `docker compose up -d`.
3. **PHP Dependencies:** `docker compose exec app composer install`.
4. **JS Dependencies:** `docker compose exec app pnpm install`.
5. **Key:** `docker compose exec app php artisan key:generate`.
6. **Database:** `docker compose exec app php artisan migrate --seed`.

### Build Sequence:

> **Asset compilation for the prototype (Vite v8 + Tailwind v4):**

- **Development:** `docker compose exec app pnpm dev`.
- **Production:** `docker compose exec app pnpm build`.

### Test Commands:

> **Validation of SOFIA PLUS import logic:**

- **General:** `docker compose exec app php artisan test`.
- **Importer Module:** `docker compose exec app php artisan test --filter ImportExcelTest`.

### Lint/Run Scripts:

> **Code maintenance and Cleanup:**

- **Style:** `docker compose exec app php vendor/bin/pint`.
- **Cache:** `docker compose exec app php artisan optimize:clear`.

### Preconditions & Postconditions:

- **Preconditions:**
    - Docker Engine v29.1+ active on the host (Linux Mint).
    - Ports 8080 and 3306 available for container mapping.
- **Postconditions:**
    - Verify the `app` service has write permissions for `storage/` and `bootstrap/cache/`.
    - Validate that `apprentices` and `evaluative_judgments` tables exist after migration.

## 3. Project Layout and Architecture

- **Architectural Elements:**
    - **Separation of Concerns:** All complex business logic, extensive validations, and external service integrations must be encapsulated in a Service Layer (`app/Services/`). Controllers must remain "Lean".
    - **Reactive Components:** Frontend interactivity is managed via modular **Livewire v4** and **Alpine.js** components, avoiding custom JavaScript scripts outside these components.
    - **CSS-First Theme:** Design follows a CSS-first approach using **TailwindCSS v4**. No JS configuration files are used for styling; all customization resides in the main Laravel style file.

- **Relative Paths to Main Files:**
    - `app/Services/`: Main container for business logic and data processing.
    - `resources/views/livewire/`: Main directory for Livewire Single-file Components (combining PHP logic and Blade markup in a single file).
    - `database/migrations/`: Single source of truth for the MySQL database schema.
    - `resources/css/app.css`: Entry point for the design system and @theme tokens.

- **Configuration Files Location:**
    - `.env`: Environment variables and sensitive credentials management.
    - `docker-compose.yml`: Development infrastructure definition.
    - `vite.config.js`: Asset bundler and frontend plugin configuration.
    - `composer.json` & `package.json`: Dependency manifests for backend and frontend.

## 4. Development Standards

- **Naming Conventions:**
    - **Code:** Variables, methods, and classes written exclusively in **English**. Names must clearly indicate functionality to avoid unnecessary comments.
    - **PSR-12 Standard:** `PascalCase` for classes, `camelCase` for methods and variables.
    - **Database:** Tables and columns in `snake_case`.
    - **Livewire Components:** kebab-case naming convention for Single-file Components (unifying logic and template into a single .blade.php file).

- **Design & UI/UX:**
    - **Visual Style:** "Industrial Bento" aesthetics—modular, structured grids with clean borders (`border-slate-100`), soft drop shadows (`shadow-sm`), and clear content separation for an enterprise-grade medical logistics application.
    - **Color Palette:** SENA institutional colors optimized for a medical dashboard. Uses an ultra-clean slate/gray background (`bg-slate-50`) to ensure high contrast, using deep tech blue (`#00539C` / `bg-blue-900`) and vibrant lime green (`#5CB612` / `bg-lime-500`) for data cards, interactive states, primary action buttons, and visual badges.
    - **Tailwind v4:** Utility-first approach and native CSS variables within `app.css`.
    - **Components:** High-density, scannable layouts with distinct metric bento-cards, structured data tables featuring subtle row highlights, clear badge taxonomies for regional delivery routes (e.g., Neiva, Pitalito, Garzón), and dedicated UI containers for logistics tracking maps.

- **Code Patterns & Best Practices:**
    - **Logic:** Follow SOLID principles, prioritizing **Single Responsibility**.
    - **Control Flow:** Mandatory use of **Early Returns**; avoid `else` statements whenever possible.
    - **Typing:** Strict typing for all parameters and return values in PHP 8.3+.
    - **Return Types:** Always explicitly declare the return type of a method.
    - **Slim Controllers:** All heavy business logic belongs to the Service Layer (`app/Services/`).

- **Language Preferences & Documentation:**
    - **Source Code:** English for all logical structures (variables, functions, classes).
    - **Interface (UI):** Labels, messages, and user-facing text in **Spanish (Colombia)**.
    - **AI Interaction:** AI explanations and responses must be in **Spanish**.
    - **Comments:** Minimalist, only for non-obvious business logic, and written in **English**.

## 5. Quality & Validation Pipelines

- **Pre-Check (Before Commit):**
    - **Code Style:** Mandatory execution of `composer lint` (Laravel Pint) to ensure PSR-12 compliance.
    - **Static Analysis:** Code must be validated with **PHPStan** (level 5 or higher) to prevent typing and logic errors.
    - **Asset Compilation:** Verify that `pnpm run build` completes without errors, ensuring TailwindCSS v4 directive integrity.

- **CI/CD Workflows:**
    - **GitHub Actions:** Automated testing in isolated containers replicating the production environment.
    - **Data Persistence:** Tests must run against the MySQL database configured in the docker-compose environment.

- **Manual Validation Steps:**
    - **Data Upload:** Perform import tests using real Excel files from current training programs to validate database integrity.
    - **Interface Review:** Confirm the "Bento" dashboard layout maintains its structure on desktop and tablet resolutions.
    - **Log Inspection:** Check `storage/logs/laravel.log` after critical processes to rule out silent errors or performance warnings.

## 6. Agent Behavior Protocol

- **Guiding Principles:**
    - **Strict Adherence:** The agent must comply with architectural instructions (Service Layer and TALL Stack) without imposing external or general styles.
    - **No Preaching:** Do not question the choice of tools or patterns defined here; focus on the requested technical implementation.
    - **Project Consistency:** Before suggesting new code, consider previous patterns, such as SENA report import logic and Livewire component management.

- **Workarounds & Hacks:**
    - **Host vs. Container Environment:** Always prioritize commands within docker compose exec. Do not suggest direct modifications to the host OS (Linux Mint) unless strictly necessary.

- **Trust Policy (Rules vs. Search):**
    - **Repository Priority:** Instructions in this `.md` file have absolute authority over general model knowledge or web search results.
    - **Local Context Validation:** Trust the file structure (Section 3) and standards (Section 4) before proposing generic paths or variable names.
