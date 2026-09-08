# AfProsPos Phase 0: Project Setup Guide

**Audience:** developers setting up AfProsPos on Windows with PowerShell  
**Outcome:** a running Laravel + Inertia + React + Tailwind application connected to local MySQL, ready for Phase 1 implementation  
**Last checked:** September 3, 2026

## 1. What This Setup Creates

The target stack is:

```text
Laravel (current stable release)
PHP 8.2 or newer
Inertia.js 3 + React 19 or newer
TypeScript + Vite
Tailwind CSS (the version supplied by the current Laravel React starter kit)
MySQL 8 / InnoDB
Node.js LTS + npm
```

Use the Laravel React starter kit for the initial setup. It is the supported fast path for a Laravel/Inertia/React application and avoids hand-wiring an adapter that the starter kit already configures. Inertia 3 requires PHP 8.2+, Laravel 11+, and React 19+. [Inertia 3 upgrade guide](https://inertiajs.com/docs/v3/getting-started/upgrade-guide)

This setup creates development tooling and a minimal starter application. It does **not** implement an AfProsPos business module, add product data, configure a live payment provider, or grant production access.

## 2. Important Workspace Decision

The current `C:\coding\afprospos` directory contains the design and planning documentation and is not empty. Laravel's project-creation commands expect an empty target directory.

Create the initial Laravel application in a clean sibling directory, then copy the existing documentation into it:

```text
C:\coding\afprospos          <- current documentation-only workspace; preserve it
C:\coding\afprospos-app      <- clean Laravel project created in this guide
```

After the application has been verified, make `C:\coding\afprospos-app` the development repository/workspace (or migrate it to the desired final repository location through an approved source-control move). Do not delete or overwrite the current documentation folder to make room for the framework.

> Do not put the Laravel application in a nested `backend/` folder merely to avoid this decision. The architecture and path conventions in the implementation plan assume the Laravel project is the repository root.

## 3. Install the Local Prerequisites

### 3.1 Recommended Windows route: Laravel Herd

Install [Laravel Herd for Windows](https://herd.laravel.com/windows). Laravel documents Herd as a native Windows/macOS development environment that supplies PHP, Composer, the Laravel installer, Node, npm, and nvm. MySQL remains a separate local service unless managed by the selected Herd edition. [Laravel installation documentation](https://laravel.com/docs/12.x/installation)

After installing Herd, close and reopen PowerShell so its command-line tools are available.

### 3.2 MySQL 8

Install MySQL 8 Community Server using the official installer or a managed local development service. During installation:

1. Record the local root/admin password in a password manager; do not place it in source control.
2. Install the MySQL command-line client if it is not selected by default.
3. Run MySQL as a local service.
4. Prefer `127.0.0.1:3306` for local application configuration.

AfProsPos requires MySQL 8 with InnoDB because its reservation design depends on transactional row locking and `SELECT ... FOR UPDATE`.

### 3.3 Alternative route: install tools separately

If Herd is not used, install and put these programs on `PATH`:

- PHP 8.2+ with `curl`, `mbstring`, `openssl`, `pdo_mysql`, `xml`, and `zip` enabled.
- [Composer](https://getcomposer.org/download/).
- [Node.js LTS](https://nodejs.org/).
- [Git for Windows](https://git-scm.com/download/win).
- MySQL 8 as described above.

Then install the Laravel installer globally:

```powershell
composer global require laravel/installer
composer global config bin-dir --absolute
```

Add the printed Composer global bin directory to the current user's `PATH`, close PowerShell, and open a new window. The command `laravel --version` must work before proceeding.

## 4. Verify the Toolchain

Run these commands in a **new** PowerShell window:

```powershell
php --version
composer --version
laravel --version
node --version
npm --version
mysql --version
git --version
```

Expected result:

- PHP is 8.2 or newer.
- Node is a supported current LTS release (Node 20+ is a sensible minimum for the current Tailwind/Vite toolchain).
- MySQL reports version 8.x.
- Every command returns a version instead of a "command not found" error.

If one fails, fix the installation/PATH before creating the project. Do not work around a missing PHP, Node, or MySQL installation by committing generated artifacts from another machine.

## 5. Create the Laravel + Inertia + React Project

### 5.1 Create a clean application directory

From the parent directory, run:

```powershell
Set-Location C:\coding
laravel new afprospos-app
```

When the Laravel installer prompts for choices, choose:

| Prompt | Choice for AfProsPos |
|---|---|
| Starter kit | **React** |
| Authentication | Laravel's built-in/session-based option |
| Testing framework | Pest or PHPUnit, according to the team standard; use one consistently |
| Database | MySQL (or configure it immediately after scaffolding) |
| TypeScript | **Yes** |

The precise wording of installer prompts can change. The required outcome is a Laravel application containing Inertia's Laravel adapter and the `@inertiajs/react` frontend adapter, not a Vue/Livewire application.

> Do not install `react-router-dom`. Laravel routes are the only navigation authority; Inertia handles visits between server-owned pages.

### 5.2 Confirm the generated frontend stack

```powershell
Set-Location C:\coding\afprospos-app
composer show inertiajs/inertia-laravel
npm ls @inertiajs/react react react-dom
Get-Content package.json
```

Confirm that the Composer package list includes `inertiajs/inertia-laravel`, and the npm dependency tree includes `@inertiajs/react`, `react`, and `react-dom`.

If the installer did not install frontend packages, run:

```powershell
npm install
npm run build
```

The React starter kit is preferred over manual installation. Laravel's starter kits are explicitly documented as the fastest way to start an Inertia application with React. [Inertia server-side setup](https://inertiajs.com/docs/v2/installation/server-side-setup)

## 6. Configure MySQL and Application Secrets

### 6.1 Create the local database

Run the following from PowerShell, replacing the placeholder with the password entered during MySQL installation when prompted:

```powershell
mysql -u root -p -e "CREATE DATABASE afprospos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Use a database name dedicated to this local application. The `utf8mb4` and `utf8mb4_unicode_ci` defaults match the database design document.

### 6.2 Configure `.env`

The installer normally creates `.env`. If it does not, copy the example first:

```powershell
Copy-Item .env.example .env
```

Update these values in `.env` using an editor; values shown in angle brackets are placeholders, not literal text:

```dotenv
APP_NAME=AfProsPos
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=afprospos
DB_USERNAME=<local_mysql_user>
DB_PASSWORD=<local_mysql_password>
```

Then generate the application key and test the database connection:

```powershell
php artisan key:generate
php artisan migrate
```

The starter kit may create a temporary default authentication schema. Do not build AfProsPos business behavior on its generated `App\Models\User` or generic `users` table. In Phase 1, replace the starter authentication persistence with the documented Identity/Shared-Kernel design (`staff` and `customers`, through appropriate guards/providers) and remove obsolete scaffolding in the same change. This prevents an undocumented second source of identity truth.

## 7. Run and Verify the Development Environment

### 7.1 Start all development processes

Laravel's current application scripts typically start the PHP server, queue listener, log viewer, and Vite together:

```powershell
composer run dev
```

If the generated application does not define that script, run these in separate PowerShell windows:

```powershell
php artisan serve
```

```powershell
npm run dev
```

The PHP server normally appears at `http://127.0.0.1:8000`. Open the URL and verify the React starter page loads with no browser-console or Vite errors.

### 7.2 Run the baseline checks

```powershell
php artisan about
php artisan route:list
php artisan migrate:status
php artisan test
npm run build
```

All commands should complete successfully before any Phase 1 code is added. If a generated test fails, resolve the environment issue first rather than deleting the test.

### 7.3 Verify Inertia specifically

Inspect the generated app entry point (normally `resources/js/app.tsx` or similar) and verify that it uses `createInertiaApp` from `@inertiajs/react`. Inspect the root Blade view and verify it contains the Inertia directives. Laravel's Inertia adapter uses an Inertia root template and middleware to share server data/version assets. [Inertia server-side setup](https://inertiajs.com/docs/v2/installation/server-side-setup)

Do not add a CSRF meta-tag workaround. Laravel already handles CSRF protection for its normal Inertia requests. [Inertia CSRF protection](https://inertiajs.com/docs/v2/security/csrf-protection)

## 8. Preserve and Bring Across the AfProsPos Documentation

After the generated application passes the baseline checks, copy—not move—the documentation into the new project:

```powershell
Copy-Item -Recurse C:\coding\afprospos\documentation C:\coding\afprospos-app\documentation
```

Review the copied files and commit them with the bootstrap once the destination project has source control. Retain the original documentation-only folder until the team has verified the new project workspace and backup/source-control state.

## 9. Phase 0 Architecture Alignment Tasks

Complete these immediately after framework setup and before building a business feature:

1. Configure Composer autoloading for `domain/` and create the canonical bounded-context skeleton from the implementation plan.
2. Create route files for Admin, Customer, Webhooks, API, and console schedules; do not introduce a client-side router.
3. Add service providers and binding locations for each bounded context.
4. Add the Shared Kernel primitives (`Money`, IDs, clock, transaction boundary, event metadata) without turning it into a catch-all utility folder.
5. Configure test MySQL 8, static analysis, code formatting, TypeScript checking, frontend lint, architectural tests, and CI before starting Phase 1.
6. Set migration defaults to `InnoDB`, `utf8mb4`, and `utf8mb4_unicode_ci` and follow the documented migration order.
7. Plan the starter-auth replacement as the first Phase 1 implementation task; do not extend the generated generic user model with repairs, sales, payments, or roles.

## 10. Manual Inertia Installation (Only for an Existing Laravel App)

Do **not** use this path for the new AfProsPos application unless the React starter kit cannot be used. It exists for the case where a pre-existing Laravel app must be converted without replacing its base project.

1. Install the Laravel adapter:

   ```powershell
   composer require inertiajs/inertia-laravel
   php artisan inertia:middleware
   ```

2. Install React, the Inertia React adapter, and the React Vite plugin:

   ```powershell
   npm install react react-dom @inertiajs/react
   npm install --save-dev @types/react @types/react-dom @vitejs/plugin-react typescript
   ```

3. Configure the React Vite plugin, the Inertia root Blade template, the `HandleInertiaRequests` middleware, and a `resources/js/app.tsx` entry using `createInertiaApp`.
4. Add a test controller that returns `Inertia::render(...)`, then run `npm run build` and the application test suite.

This manual route has more configuration surface and makes it easier to miss a middleware, Vite, or TypeScript setting. Prefer the React starter kit for new work.

## 11. Tailwind Setup Notes

The current Tailwind Laravel/Vite guidance installs `tailwindcss` and `@tailwindcss/vite`, imports Tailwind in the main CSS file, and starts the Vite process with `npm run dev`. [Tailwind's Laravel/Vite guide](https://tailwindcss.com/docs/installation/framework-guides/laravel/vite)

For AfProsPos:

1. Keep the Tailwind version delivered by the current React starter kit; do not downgrade or upgrade it during project bootstrap.
2. In Phase 2, add the AfProsPos token source under `resources/css/` and import it from the main app stylesheet.
3. Map Admin and Customer semantic colours to tokens. Raw hex values are permitted only in the central token definition, never in pages/components or arbitrary Tailwind classes.
4. Do not add Sass solely for Tailwind; the current Tailwind version is designed to work directly with CSS/Vite.

## 12. Setup Completion Checklist

- [x] PHP 8.2+, Composer, Laravel installer, Node/npm, Git, and MySQL 8 are installed and visible in new PowerShell windows.
- [x] `C:\coding\afprospos-app` is a clean Laravel project created with the React starter kit.
- [x] `inertiajs/inertia-laravel` and `@inertiajs/react` are installed.
- [x] The app builds with `npm run build` and starts with `composer run dev` (or PHP/Vite processes separately).
- [x] MySQL database `afprospos` exists with `utf8mb4` / `utf8mb4_unicode_ci`.
- [x] `.env` contains local-only credentials and is excluded from source control.
- [x] `php artisan migrate`, `php artisan test`, and `php artisan route:list` succeed.
- [x] The generated page loads in the browser with no Vite or console errors.
- [x] Existing AfProsPos documentation has been copied into the new project and the original has been retained until workspace migration is confirmed.
- [x] No business feature has been added to the generated generic starter authentication model.

## 13. Troubleshooting

| Symptom | Likely cause and action |
|---|---|
| `laravel` is not recognised | Close/reopen PowerShell after Herd installation, or add the Composer global bin directory printed by `composer global config bin-dir --absolute` to `PATH`. |
| `php` is not recognised or reports an old version | Correct the PHP/Herd PATH; do not continue with PHP below 8.2. |
| `npm install` or Vite fails | Verify Node LTS is in the new PowerShell session, delete only the project's generated `node_modules`/lockfile when diagnosing a known dependency issue, then reinstall; never delete documentation or source directories. |
| `php artisan migrate` cannot connect | Confirm MySQL service is running, credentials are correct, and the database exists. Test with `mysql -u <user> -p -h 127.0.0.1`. |
| Browser is blank or page assets return 404 | Start `npm run dev`, check the Vite terminal, and confirm the root Blade file invokes Vite/Inertia as supplied by the starter kit. |
| React dependencies are missing | Re-run `npm install`; if the initial installer did not select React, recreate the clean bootstrap directory and choose the React starter kit rather than attempting to mix Vue/React scaffolding. |
| A business feature needs `App\\Models\\User` | Stop and implement/confirm the Phase 1 Identity guard/provider design first. The final architecture must not retain an undocumented generic identity model. |
