# Containerize Legacy Laravel Backend

## Goal
Run the existing Laravel 8 API and MySQL through Podman Compose without requiring host PHP or Composer.

## Scope and assumptions
- This repository contains the legacy backend only; the separate Vue frontend repository is not present here.
- Preserve the current Laravel 8 / PHP 8.1 container runtime rather than silently migrating to Laravel 11 / PHP 8.2.
- Add a PHP runtime image with required MySQL extension and a Compose `app` service alongside MySQL.
- Keep host PHP and Composer unnecessary.

## Tasks
1. Add a backend PHP container build definition suitable for the current Laravel application and update Compose with service wiring and startup command.
2. Validate the Compose configuration with the available Podman Compose provider; document any runtime limitations.

## Acceptance checks
- Compose configuration parses with `podman-compose config`.
- App service connects to MySQL using service DNS, publishes an API port, and executes dependency setup/migrations before serving.
- No claim that the absent frontend is included.

## Evidence
- `podman-compose config` passed with both `app` and `mysql` services, health-gated startup, DB environment, and API port 8000.
- PHP 8.1 image build and container runtime verified. Migrations succeeded; `/api/health` returns HTTP 200 and the seeded admin login returned HTTP 200 after fixing the server environment pass-through.
- Laravel 8 `artisan serve` initially omitted Compose DB variables from its child when reload was enabled. `--no-reload` ensures the HTTP worker inherits `DB_HOST=mysql` and the database credentials.
- Startup migrates but does not automatically seed: the repository seeder inserts 40,000 records without idempotency, so run it manually only once to avoid duplicates.
- Strict TDD was explicitly disabled by the user for this configuration-only task; validation included Compose parsing and live container/API checks.
- Work-unit commit: `8a2a775 feat(container): run Laravel API with MySQL in Podman`.
