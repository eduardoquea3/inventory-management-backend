# Fix Local Frontend CORS

## Goal
Allow the local frontend origins `http://localhost:5173` and `http://127.0.0.1:5173` to call the Laravel API across origins.

## Scope and assumptions
- Local development only; allow the two user-confirmed origins, not all origins.
- API uses bearer tokens; do not enable credentialed cookies unless separately required.
- Register Fruitcake Laravel CORS middleware globally and configure the `api/*` path.
- Allow the `X-CSRF-TOKEN` header shown in the user's login request, in addition to JSON and Bearer headers.
- Preserve existing API behavior and unrelated uncommitted/generated files.

## Tasks
1. Add explicit CORS config and register the middleware in Laravel's global HTTP stack.
2. Add focused preflight coverage and validate both local origins.
3. Verify preflight/API response headers and report the frontend URL/action to retest.

## Acceptance checks
- OPTIONS preflight for `/api/login` accepts both confirmed origins and POST/authorization/content-type/CSRF headers.
- No permissive wildcard origin or credentialed CORS.
- Focused test and actual Podman-hosted preflight succeed.

## Evidence
- Read-only inspection found no project `config/cors.php` and no CORS middleware in `app/Http/Kernel.php`; package `fruitcake/laravel-cors` exists in dependencies.
- User confirmed both local hosts, each on port 5173.
- Registered `Fruitcake\\Cors\\HandleCors` globally; config scopes CORS to `api/*`, two explicit local origins, explicit HTTP methods/headers including `X-CSRF-TOKEN`, and `supports_credentials=false`.
- Focused PHPUnit passes: 2 tests / 16 assertions. Live OPTIONS requests for both origins return 204 and allow `authorization`, `content-type`, and `x-csrf-token`. `git diff --check` passes.
- Work-unit commit: `f41dccb fix(cors): allow local frontend origins`.
- Generated/vendor/cache/log files are left untouched and excluded from commits.
