# Add Swagger API Documentation

## Goal
Integrate interactive OpenAPI/Swagger documentation for the current legacy Laravel API so endpoints can be inspected and exercised from Swagger UI.

## Scope and assumptions
- Target the current Laravel 8.75 application and PHP 8.1 Podman runtime; do not silently migrate framework/runtime versions.
- Document the existing API routes and its legacy token authentication mechanism.
- Pin a Laravel-compatible L5-Swagger/swagger-php release; avoid current releases requiring PHP 8.2+.
- Keep Swagger UI and generated docs available for local development; no production exposure policy is set.
- Provide generation and validation Podman commands in the user handoff; README edits were excluded by the user.

## Tasks
1. Map API actions, validation payloads/responses, and auth token flow; select a package version compatible with Laravel 8/PHP 8.1.
2. Add/pin the OpenAPI package, configuration, annotations, and provider registration.
3. Generate the OpenAPI document, validate routes/UI, and run focused tests.

## Acceptance checks
- Swagger UI route resolves locally and OpenAPI JSON generation succeeds.
- Existing routes are represented with request/response details and legacy token authentication.
- Package resolution does not force an unrelated Laravel major upgrade.
- Generation and focused test commands are reproducible with Podman.

## Evidence
- Context7 package ID `/darkaonline/l5-swagger`; Composer resolved L5-Swagger 8.6.5 with PHP `^7.2 || ^8.0` and Laravel `>=8.40`, compatible with PHP 8.1 / Laravel 8.x-dev in the container.
- Exploration `gentle-ai-explore` task `muprx9os-1-xzpb` mapped 17 route operations. Custom `LegacyTokenAuth` accepts `Authorization: Bearer` and reads `users.api_token`; login and health are public; remaining endpoints are protected.
- Strict TDD explicitly selected by user. RED confirmed Swagger UI returned 404 and the spec was absent before implementation. Focused runner: `podman-compose exec -T app vendor/bin/phpunit tests/Feature/SwaggerDocumentationTest.php`.
- User authorized provider registration in `config/app.php` and generation of `storage/api-docs/api-docs.json`.
- GREEN: focused suite passes (2 tests, 11 assertions); UI `/api/documentation` and JSON `/docs` each return HTTP 200; generated JSON has 17 operations. `composer validate --no-check-publish` reports valid with only missing-license warning; `git diff --check` passes.
- Review found and removed duplicate L5-Swagger key in `composer.json`. Regenerated spec with an explicit SQL-injection-risk warning for the existing `q` filter. The vulnerable query implementation itself remains unchanged/out of scope.
- No README edits or DB seeding. Composer reported security advisories in two packages; they were not investigated or changed.
- Work-unit commit: `a085710 feat(swagger): add interactive API documentation`.
