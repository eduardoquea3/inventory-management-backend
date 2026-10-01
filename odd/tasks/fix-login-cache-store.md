# Fix API Login Cache Store

## Objective
Remove the `/api/login` HTTP 500 caused by Laravel's rate limiter querying a missing MySQL `cache` table, using the smallest guide-compatible cache setup.

## Problem and rationale
The user's exact login request returned `SQLSTATE[42S02]` for `legacy_products.cache`. The trace shows `throttle:api` reaching `Illuminate\Cache\RateLimiter` and the database cache store before the login controller. Read-only exploration found no `config/cache.php`. The guide says Redis is optional while caching is mandatory; user explicitly chose the minimal file-cache approach instead of Redis.

## Scope
- Add Laravel cache configuration with file cache as its safe default and a configured file store.
- Explicitly set `CACHE_STORE=file` in the Docker `app` environment so an existing `.env` value cannot select the absent database cache table.
- Add a focused regression test proving the file store can write and read without a DB cache table.

## Constraints and non-goals
- Do not add Redis service/client/extension; Redis is not needed for this fix.
- Do not add or run database migrations, touch MySQL data, read/edit `.env.example`, or change business data cache behavior.
- Preserve unrelated pagination changes and all other uncommitted work.
- No commit, push, PR, or release requested.

## TDD and verification
- TDD mode/source: strict TDD, explicitly selected by the user for this project work.
- RED evidence before implementation: the user's exact POST `/api/login` returned HTTP 500 with missing `legacy_products.cache`; stack trace reached the database cache store via API throttling. Add a focused regression test and observe it fail against the missing `file` store configuration before adding the implementation.
- Runner: `docker compose run --rm app php artisan test --filter=CacheStoreConfigurationTest`; full suite `docker compose run --rm app php artisan test`.
- Additional checks: `docker compose config` must show `CACHE_STORE=file`; `docker compose build app`; no live app restart or DB-mutating commands unless separately authorized.

## Tasks
- [x] T1 — Add the regression test first, observe RED, then add `config/cache.php` and Compose `CACHE_STORE=file` and observe GREEN.
- [x] T2 — Verify Compose configuration/build, focused test, and full test suite; record any limitations.
- [x] T3 — Recreate the running app container and smoke-test the login/cache path after explicit user approval, because Compose startup runs `php artisan migrate --force`.
- [x] T4 — Document the file-cache selection in README without implying Redis or catalog-result caching was added.
- [x] T5 — Check final diff/status and remove only task-generated test cache artifacts; preserve unrelated pre-existing changes.

## Acceptance criteria
- The app's Docker environment explicitly selects the file cache store.
- Laravel has a configured file cache store rooted under `storage/framework/cache/data`.
- The focused regression test proves file-cache put/get works with no MySQL cache table.
- Focused and full tests pass; Docker configuration/build passes.
- No Redis, MySQL cache-table migration, `.env.example` edit, or application DB mutation is introduced.

## Progress and evidence
- Redis assessment: not necessary for this failure; file cache avoids new infrastructure and the missing-table dependency.
- T1 complete: added a regression test, `config/cache.php` with file cache under `storage/framework/cache/data`, and Compose `CACHE_STORE=file`. The test observed RED (default `array`, expected `file`) and GREEN after implementation (1 test/3 assertions).
- T2 complete: Compose config and build passed; full suite passed (11 tests/78 assertions). No Redis, migration, DB mutation, or `.env.example` edit occurred.
- Runtime applied with explicit user approval: `docker compose up -d --build --force-recreate --no-deps app` succeeded. The MySQL container was healthy; startup installed dependencies, ran `php artisan migrate --force` and reported `Nothing to migrate`, then started the server. `legacy_api` now has PHP 8.2.34 and `CACHE_STORE=file`; `php artisan config:show cache.default` resolves `file`.
- Live smoke test after restart: the user's login curl returned HTTP 200; response body was intentionally not printed to avoid exposing an auth token.
- T4 complete: README now records `CACHE_STORE=file`, explains Redis is not part of this stack, and distinguishes rate-limit cache from unimplemented catalog/result caching. Updated test count to 11 tests / 78 assertions.

## Next step
The file-cache fix is complete. Final `git diff --check` passed; generated `.phpunit.cache/` was removed. Existing pagination edits and the earlier migration work remain in the working tree, unchanged by this cache task. No commit was created.
