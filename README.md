# Laravel API backend

A Docker Compose–hosted inventory API running **Laravel 12.69.3** on **PHP 8.2**, backed by MySQL 8.0. The Compose stack contains only the `app` and `mysql` services; there is no frontend or Nginx service.

## Quick start

Requirements: Docker Engine and the Docker Compose plugin.

```bash
docker compose up --build
```

On startup, the `app` service installs Composer dependencies, creates `.env` from `.env.example` if needed, generates an application key when missing, runs migrations, and starts Laravel's development server. The API is available at **http://127.0.0.1:8000/api**. Leave this command running while using the API.

To stop the services, press `Ctrl+C`, then run:

```bash
docker compose down
```

The MySQL data volume is retained by this command.

## Services and ports

| Service | Image/runtime | Host port | Purpose |
| --- | --- | --- | --- |
| `app` | PHP 8.2 CLI; Laravel development server | `8000` | API and Artisan/Composer commands |
| `mysql` | MySQL 8.0 | `3307` | Application database (container port `3306`) |

Compose configures the application to connect to database `legacy_products` on service host `mysql`. The Compose database credentials are development-only (`root` / `root`); do not reuse them outside a local environment.

## Common commands

Run these from the repository root. Commands that use `docker compose run` start a one-off app container and do not require the API server to be running; the MySQL service must be available.

```bash
# Start the database in the background
docker compose up -d mysql

# Run migrations and seed sample data
docker compose run --rm app php artisan migrate --seed

# Run the test suite
docker compose run --rm app php artisan test

# Follow application service output
docker compose logs -f app

# Follow database service output
docker compose logs -f mysql

# Stop and remove containers and network (keeps the named database volume)
docker compose down
```

The Compose startup command already runs migrations (without seeding). Use the explicit migration/seed command when you want sample records; the database seeder creates `admin@legacy.test` with password `password`, which is a local test credential only.

## API

Base URL: `http://127.0.0.1:8000/api`. `POST /login` and `GET /health` are public. The remaining listed routes require authentication.

| Method | Endpoint | Access |
| --- | --- | --- |
| `POST` | `/login` | Public |
| `GET` | `/health` | Public |
| `GET` | `/me` | Authenticated |
| `POST` | `/logout` | Authenticated |
| `GET` | `/dashboard` | Authenticated |
| `GET`, `POST` | `/products` | Authenticated |
| `GET`, `PUT`, `DELETE` | `/products/{id}` | Authenticated |
| `GET`, `POST` | `/products/{id}/stock-movements` | Authenticated |
| `GET`, `POST` | `/categories` | Authenticated |
| `GET`, `PUT`, `DELETE` | `/categories/{id}` | Authenticated |

## Stack and current status

- Composer requires PHP `^8.2` and Laravel `^12.0`; the lockfile resolves Laravel **12.69.3**.
- The PHPUnit suite is enabled and can be run with the command above. The latest run passed (11 tests, 78 assertions); rerun it against your current checkout to verify locally.
- Compose sets `CACHE_STORE=file` so API throttling does not depend on a MySQL `cache` table. Redis is optional and is not part of this stack.
- Composer dependency validation passed. A Composer audit reported no known vulnerabilities but exited non-zero because the transitive `doctrine/annotations` package is abandoned and has no suggested replacement.
- The database seeder provides sample data and a test account; do not treat its credentials as production credentials.
- `.env.example` has not been reviewed or changed as part of this migration and remains for manual review. The Compose startup flow may copy it to `.env`; review environment configuration yourself before use beyond local development.
- This repository remains an intentionally legacy technical-test application. The migration does not claim full compliance with the technical guide or implement its broader audit, catalog/result caching and invalidation, frontend, Telescope, business-rule, or performance work. Compose has no frontend or Nginx service.
