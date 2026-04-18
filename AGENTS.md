# AGENTS.md

## Project Snapshot

- PHPGit is a small, server-rendered PHP app (school project) with a single entry point: `src/index.php`.
- App code lives in `src/`; Composer autoload maps `App\\` to `src/` (`composer.json`).
- API v1 system endpoints are file entry points under `src/api/v1/system/` that bootstrap Composer and dispatch to
  `App\\Controllers\\ApiController`. Additional non-system endpoints (`getDashboardInfo.php`, `getDatabaseUptime.php`,
  `health.php`) live directly under `src/api/v1/` and route through `ApiController::api()`.
- Runtime config is loaded from `src/.env` via `vlucas/phpdotenv` (`src/Config.php`).
- Web server document root is expected to be `src` (README installation notes).
- composer.json requires PHP ^8.4 and the extensions `ext-pdo` and `ext-http` (see `composer.json`).
- Application writes runtime logs to `src/log/`; helper logging is implemented in `App\includes\Logging` (
  `src/includes/Logging.php`).

## Architecture and Request Flow

- `src/index.php` is the thin front controller: registers `ErrorHandler` and delegates everything to
  `(new PageController())->run()`.
- All page-dispatch logic lives in `App\Controllers\PageController` (`src/Controllers/PageController.php`):
  - Starts session (30-day cookie, `httponly`, `samesite=Strict`, domain `phpgit.local`, ID regeneration on first
    visit).
  - Resolves `?page=...` and enforces access via three route constant groups:
    - `PAGE_TITLES` — public routes available to everyone
    - `AUTHENTICATED_USER_PAGES` (`settings`, `repos`, `new_repo`) — require `$_SESSION['is_logged_in']`
    - `ADMIN_PAGES` (`dashboard`) — require `$_SESSION['role'] === 'ADMIN'`
  - Blocked routes (`env`, `htaccess`, `config`) return 403 via `RESTRICTED_PAGES`.
  - Calls `include __DIR__ . '/../views/layout.php'` to render, passing `$page`, `$page_title`, `$is_logged_in`,
    `$is_dev`, `$role`, `$pdo`, `$config`, `$username`.
- `src/views/layout.php` wraps every page: inlines the theme-init script, loads CDN Bootstrap 5 + Bootstrap Icons,
  resolves versioned asset URLs via `App\includes\Assets`, then includes:
    - `src/includes/header.php`
    - `src/pages/<page>.php`
    - `src/includes/footer.php`
  - Extra assets loaded conditionally: `terminal.css` / `terminal-animation.js` for `login`/`register`; `admin.css` for
    ADMIN role; `dev.css` when `APP_ENV=dev`.
- Allowed routes are driven by `PageController` route constants plus:
    - `phpinfo` only when `APP_ENV=dev`
- `src/Config.php` is a singleton used at bootstrap; it opens PDO once and exposes DB/dev-state getters used by
  `PageController`
  and `DevPanel`.
- API responses are standardized through `App\Core\Controller` helpers (`json`, `success`, `error`, method guards), and
  system metrics are provided by `App\Services\SystemService`.
- Error behavior is environment-sensitive in `src/includes/ErrorHandler.php` (`App\includes\ErrorHandler`),
  registered at bootstrap in `src/index.php`:
    - `dev`: verbose in-browser error/exception UI
    - `prod`: suppress display, log server-side, send generic 500 text

## Auth, Security, and Session Patterns

- Auth state is session-first (`$_SESSION['is_logged_in']`, `user_id`, `username`, `role`) set in `src/pages/login.php`
  and cleared in `src/pages/logout.php`.
- CSRF and login throttling are implemented in `App\includes\Security` (`src/includes/Security.php`), instantiated in
  pages like `src/pages/login.php`, `src/pages/register.php`, and `src/pages/contact.php`.
- Forms follow a consistent pattern: validate inputs, use prepared PDO statements, escape output with
  `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- `settings` is rendered via `App\includes\Settings` (`src/includes/Settings.php`) with tab whitelisting (`profile`,
  `security`, `appearance`, `notifications`, `privacy`, `ssh-keys`) and a guest 403 view. Tab files live in
  `src/pages/tab/`.

## Developer Workflow (Discoverable)

- Install dependencies with Composer:
    - `composer install`
- Create runtime env from template before local run:
    - copy `src/.env.example` -> `src/.env` and set DB credentials.
- Run the installer to initialise the database and configure Apache:
  - `php installer.php` (prompts for `ServerName` and HTTPS preference, updates `apache/phpgit.local.conf`)
- Enable Apache `rewrite` + `headers`, and protect sensitive files/dirs with `.htaccess` rules shown in `README.md`.
- There is no automated test suite/config in this repository (no `phpunit.xml*` or test directory detected).

## Frontend and External Integrations

- UI is Bootstrap 5 + Bootstrap Icons from CDN in `src/views/layout.php`; custom assets are under `src/assets/`.
- Theme is client-side via `localStorage` (`src/assets/js/theme.js`), toggling `data-bs-theme` on `<html>`; the init
  snippet is inlined in `layout.php` to prevent flash.
- Asset URLs are fingerprinted/versioned via `App\includes\Assets` reading `src/assets/manifest.json` (built by
  `App\includes\AssetManifestBuilder` / `bin/build-assets.php`). Always use `Assets::url('assets/...')` rather than
  hard-coded paths.
- Dev-only diagnostics panel (`src/includes/DevPanel.php`) is injected from `PageController::renderDevPanel()` when
  `APP_ENV=dev` and includes DB/session/request timing popovers.

## Agent Guardrails for This Repo

- Prefer adding new pages as `src/pages/<name>.php` and registering title/route in the appropriate constant in
  `PageController` (`PAGE_TITLES`, `AUTHENTICATED_USER_PAGES`, or `ADMIN_PAGES`).
- Keep sanitization style consistent with existing code (`preg_replace` route/tab filtering + `htmlspecialchars` on
  output).
- Do not edit `vendor/`; use Composer-managed dependencies and update `composer.json` only when needed.
- If touching auth or forms, preserve CSRF token checks and rate-limit behavior from `src/includes/Security.php`.
- If adding API endpoints, keep file entry points thin and route logic through controllers/services rather than inline
  script responses. System metric endpoints go under `src/api/v1/system/`; other endpoints go under `src/api/v1/` and
  are dispatched by `ApiController::api()`.
- Admin-only API methods must call `$this->requireAdminSession()` (checks `is_logged_in` + `role === 'ADMIN'`);
  user-only methods call `$this->requireLoggedInSession()`.
