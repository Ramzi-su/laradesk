# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

LaraDesk is a support ticket platform (clients open tickets, agents handle them, admins manage everything).
It is a **portfolio project for a Laravel developer application**: clean, idiomatic Laravel matters more
than feature count. Prefer framework features (Policies, Form Requests, API Resources, Notifications,
scopes, factories) over custom abstractions. The full functional spec is `LARADESK_PLAN.md` (in French);
this file records the decisions taken where the plan was ambiguous or superseded.

> Status: scaffolded (Laravel + Breeze + Sail), domain not implemented yet. Keep this file in sync
> with reality as code lands.

## Stack

- Laravel 13, PHP 8.5, MySQL 8.4, Blade + Tailwind via Laravel Breeze, Vite, Chart.js (npm)
- Sanctum (API tokens), PHPUnit, Laravel Pint
- Laravel Sail (Docker) for local dev, GitHub Actions for CI
- Queue driver `database`; mail driver `log` locally

## Commands

Commands are shown with Sail (`alias sail='./vendor/bin/sail'`). The main developer works without Sail,
on a local PHP 8.5 + MySQL 8.0 (`DB_HOST=127.0.0.1`, databases `laradesk` and `testing`): drop the
`sail` prefix (`php artisan test`, `vendor/bin/pint --test`, `npm run dev`).

```bash
sail up -d                                  # start containers
sail artisan migrate:fresh --seed           # reset DB with demo data
sail npm run dev                            # Vite dev server
sail artisan queue:work                     # process queued notifications
sail artisan schedule:work                  # run the scheduler locally (tickets:close-stale)

sail artisan test                           # full test suite
sail artisan test --filter=TicketPolicyTest # single class or method
sail artisan test tests/Feature/Api         # single directory

sail bin pint                               # fix code style
sail bin pint --test                        # check style (as CI does)
```

## Domain rules

- **Roles**: `users.role` cast to `App\Enums\UserRole` (`client`, `agent`, `admin`). `role` is never
  mass-assignable; registration always creates a `client`.
- **Status / priority**: string columns cast to backed enums `TicketStatus`
  (`open`, `in_progress`, `resolved`, `closed`) and `TicketPriority` (`low`, `medium`, `high`, `urgent`).
  No MySQL `ENUM` columns: allowed values live in PHP.
- **Reference**: `tickets.reference` (`LD-000123`) is derived from the auto-increment `id` in the
  `created` event (not `creating`, where the id does not exist yet; "max + 1" would race).
- **Lifecycle**: `resolved_at` / `closed_at` are set when the status changes. `Comment` declares
  `$touches = ['ticket']`, so `tickets.updated_at` is the "last activity" date.
  `tickets:close-stale {--days=7}` (scheduled daily) closes `resolved` tickets whose `updated_at`
  is older than N days.
- **Internal comments** (`comments.is_internal`) are agent/admin notes and must never reach a client:
  filter them in queries, views and API Resources.
- **Attachments** live on the private `local` disk and are only served through an authorized
  download route. Never generate public URLs for them.

## Conventions

- **Authorization**: one Policy per model; admins are granted access in `before()`. A client only ever
  sees their own tickets. Every access path (web, API, attachment download) must be scoped and
  authorized, so an ID in the URL is never enough (IDOR).
- **Validation**: Form Requests only; enum fields validated with `Rule::enum()`.
- **Listing/filters**: shared Eloquent scopes (`forUser($user)`, `status()`, `priority()`, ...) used
  by both the web controllers and the API, so filtering rules exist in one place.
- **API**: versioned under `/api/v1`, `auth:sanctum`, always API Resources (never raw models),
  always paginated.
- **Notifications** implement `ShouldQueue`.
- **Tests**: feature tests for every endpoint and policy rule, using factory states and
  `Notification::fake()`, `Storage::fake()`, `Queue::fake()`. Tests run on MySQL, like production:
  the `testing` database is created by Sail's MySQL container, and CI uses a MySQL service
  (`.github/workflows/ci.yml`). Tests need the Sail containers running.
- **Language**: code, identifiers and comments in English; UI text in French through `__()` and
  `lang/fr` (`APP_LOCALE=fr`, base translations from `laravel-lang/common`, app keys in `lang/fr.json`).
- Commits follow Conventional Commits.
- Tests use MySQL, not SQLite as the plan suggested (production parity; the dashboard uses
  MySQL-specific functions such as `TIMESTAMPDIFF`).
