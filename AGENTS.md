# AGENTS.md

## Project Snapshot

- PHPGit is a small, server-rendered PHP app (school project) with a single entry point: `src/Index.php`.
- App code lives in `src/`; Composer autoload maps `App\\` to `src/` (`composer.json`).
- API v1 system endpoints are file entry points under `src/api/v1/system/` that bootstrap Composer and dispatch to `App\\Controllers\\ApiController`.
- Runtime config is loaded from `src/.env` via `vlucas/phpdotenv` (`src/Config.php`).
- Web server document root is expected to be `src` (README installation notes).
- composer.json requires PHP ^8.4 and the extensions `ext-pdo` and `ext-http` (see `composer.json`).
- Application writes runtime logs to `src/log/`; helper logging is implemented in `App\includes\Logging` (
  `src/includes/Logging.php`).

## Architecture and Request Flow

- `src/Index.php` is the front controller: starts session, resolves `?page=...`, enforces restricted pages, then
  includes:
    - `src/includes/header.php`
    - `src/pages/<page>.php`
    - `src/includes/footer.php`
- Allowed routes are driven by `Index::PAGE_TITLES` plus conditional pages:
    - `phpinfo` only when `APP_ENV=dev`
    - `settings` only when `$_SESSION['is_logged_in']` is true
- `src/Config.php` is a singleton used at bootstrap; it opens PDO once and exposes DB/dev-state getters used by `Index`
  and `DevPanel`.
- API responses are standardized through `App\Core\Controller` helpers (`json`, `success`, `error`, method guards), and
  system metrics are provided by `App\Services\SystemService`.
- Error behavior is environment-sensitive in `src/includes/ErrorHandler.php` (`App\includes\ErrorHandler`),
  registered at bootstrap in `src/Index.php`:
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
  `security`) and a guest 403 view.

## Developer Workflow (Discoverable)

- Install dependencies with Composer:
    - `composer install`
- Create runtime env from template before local run:
    - copy `src/.env.example` -> `src/.env` and set DB credentials.
- Enable Apache `rewrite` + `headers`, and protect sensitive files/dirs with `.htaccess` rules shown in `README.md`.
- There is no automated test suite/config in this repository (no `phpunit.xml*` or test directory detected).

## Frontend and External Integrations

- UI is Bootstrap 5 + Bootstrap Icons from CDN in `src/Index.php`; custom assets are under `src/assets` and
  `src/scripts`.
- Theme is client-side via `localStorage` (`src/scripts/theme.js`), toggling `data-bs-theme` on `<html>`.
- Dev-only diagnostics panel (`src/includes/DevPanel.php`) is injected from `Index` when `APP_ENV=dev` and includes
  DB/session/request timing popovers.

## Agent Guardrails for This Repo

- Prefer adding new pages as `src/pages/<name>.php` and registering title/route in `Index::PAGE_TITLES`.
- Keep sanitization style consistent with existing code (`preg_replace` route/tab filtering + `htmlspecialchars` on
  output).
- Do not edit `vendor/`; use Composer-managed dependencies and update `composer.json` only when needed.
- If touching auth or forms, preserve CSRF token checks and rate-limit behavior from `src/includes/Security.php`.
- If adding API endpoints, keep file entry points thin and route logic through controllers/services rather than inline
  script responses.

