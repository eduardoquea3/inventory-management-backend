# Laravel API backend

A Docker Compose–hosted inventory application running **Laravel 12.69.3** on **PHP 8.2**, backed by MySQL 8.0, with the sibling Vue frontend served by Nginx.

## Quick start

Requirements: Docker Engine with the Compose plugin, or Podman with `podman-compose`. Clone both independent repositories as siblings; Compose builds the frontend Dockerfile using `../frontend-legacy-vue2`.

```bash
git clone <BACKEND_REPOSITORY_URL> backend-legacy-laravel8
git clone <FRONTEND_REPOSITORY_URL> frontend-legacy-vue2
cd backend-legacy-laravel8
```

```bash
# Docker Engine
docker compose up --build

# Podman Compose (rootless)
BACKEND_DNS_RESOLVER=10.89.1.1 podman-compose up --build
```

On startup, Compose builds the frontend and starts MySQL, the API, and Nginx. The `app` service installs Composer dependencies, creates `.env` from `.env.example` if needed, generates an application key when missing, runs migrations, and starts Laravel's development server. The integrated UI is at **http://127.0.0.1:8081** and the API is at **http://127.0.0.1:8000/api**. Override `FRONTEND_PORT` if needed.

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
| `frontend` | Vue static build served by Nginx | `8081` | Inventory UI; proxies `/api` to `app:8000` |

Compose configures the application to connect to database `legacy_products` on service host `mysql`. The Compose database credentials are development-only (`root` / `root`); do not reuse them outside a local environment.

## Common commands

Run these from the repository root. Commands that use `docker compose run` start a one-off app container and do not require the API server to be running; the MySQL service must be available. Tests explicitly force SQLite in-memory even though the app service uses MySQL.

```bash
# Start the database in the background
docker compose up -d mysql

# Run migrations and seed sample data
docker compose run --rm app php artisan migrate --seed

# Run the test suite against isolated in-memory SQLite
docker compose run --rm app sh -c 'APP_ENV=testing TELESCOPE_ENABLED=false DB_CONNECTION=sqlite DB_DATABASE=:memory: CACHE_STORE=array php artisan test'

# Follow application service output
docker compose logs -f app

# Follow database service output
docker compose logs -f mysql

# Stop and remove containers and network (keeps the named database volume)
docker compose down

# Restart the API container
docker compose restart app
```

Troubleshooting: if the API does not start, inspect `docker compose logs app` and `docker compose logs mysql`; the app waits for MySQL health before running migrations. If a host port is occupied, adjust `APP_HOST_PORT`, `MYSQL_HOST_PORT`, or `FRONTEND_PORT` in the shell/Compose environment. Docker Engine uses its embedded DNS resolver `127.0.0.11`; for Podman set `BACKEND_DNS_RESOLVER=10.89.1.1`. The frontend context must exist at `../frontend-legacy-vue2`.

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

#### List filters and sorting

`GET /api/products` accepts `q`, `category_id`, `status`, `min_price`, `max_price`, `min_stock`, `max_stock`, `sort_by`, `sort_direction`, `page`, and `per_page`. Product `sort_by` supports `created_at`, `name`, `price`, and `stock`; `sort_direction` is `asc` or `desc`. The default is `created_at desc`. Price and stock bounds are inclusive.

Example: `GET /api/products?q=keyboard&category_id=2&status=1&min_price=10&max_price=100&min_stock=1&sort_by=price&sort_direction=asc&per_page=20&page=1`

`GET /api/categories` accepts `q` (substring match on name), `status`, `sort_by` (`created_at` or `name`), `sort_direction` (`asc` or `desc`), `page`, and `per_page`. The default is `created_at desc`.

Example: `GET /api/categories?q=office&status=1&sort_by=name&sort_direction=asc`

Both endpoints return the existing standardized success envelope: `{ "success": true, "data": [...], "meta": { "pagination": { "current_page", "per_page", "total", "last_page", "from", "to" } } }`. Invalid filters, ranges, or sort values return HTTP 422 with `error.code: "VALIDATION_FAILED"`.

#### Stock movements

Register an inventory entry with `POST /api/products/{id}/stock-movements` and list that product's movement history with `GET /api/products/{id}/stock-movements`. Both require `Authorization: Bearer <token>`. The list route returns the movement rows under `data`; `GET /api/products` is the product catalog and only includes the `total_movements` count, not the movement history.

```json
{
  "success": true,
  "data": {
    "product": { "id": 10516, "stock": 17 },
    "movement": {
      "id": 42,
      "product_id": 10516,
      "type": "entrada",
      "quantity": 12,
      "reason": "Reposición de inventario"
    }
  }
}
```

To retrieve the history, call `GET /api/products/10516/stock-movements`; its response is `{ "success": true, "data": [{ "id": 42, "product_id": 10516, "type": "entrada", "quantity": 12, "reason": "Reposición de inventario" }] }`.

#### Inventory rules and operational tools

- A stock exit greater than the locked product balance returns HTTP 409 `INSUFFICIENT_STOCK`; the stock and movement history remain unchanged.
- Category deletion detaches its products by setting `category_id` to `null`. Product deletion returns HTTP 409 `PRODUCT_HAS_STOCK_HISTORY` when movements exist.
- Product/category writes and stock movements write actor/action/entity/value snapshots to `audit_logs` in the same transaction.
- Low-stock dashboard threshold defaults to `< 10`; configure `LOW_STOCK_THRESHOLD` to change it.
- Product/category list and dashboard results are cached for five minutes. Writes rotate a version key to invalidate old catalog entries using the file cache driver.
- `docker compose run --rm app php artisan db:seed --class='Database\Seeders\InventoryVolumeSeeder'` seeds the volume dataset. It generates 100 categories, 10,000 products and 30,000 movements with a consistent final stock for each generated product.
- Compare the reconstructed legacy N+1 path with the optimized listing using `docker compose exec app php artisan catalog:benchmark --per-page=100`. On the seeded 10,000-product dataset, one local run measured 201 queries/136.47 ms for the reconstructed legacy path, 3/35.84 ms uncached, and 0/1.72 ms on a cache hit; timings vary by environment.

### JSON response contract

Every successful API response uses `success: true` and a `data` property. Errors use `success: false` and an `error` object with a stable `code`, human-readable `message`, and optional `details` (validation errors are keyed by field). HTTP status codes remain authoritative.

```json
{
  "success": true,
  "data": { "id": 12, "name": "Example" }
}
```

Paginated product and category lists return the same `success`/`data` envelope and pagination metadata under `meta.pagination`:

```json
{
  "success": true,
  "data": [],
  "meta": {
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 0,
      "last_page": 1,
      "from": null,
      "to": null
    }
  }
}
```

Validation failures return HTTP 422 with `error.code: "VALIDATION_FAILED"` and field-specific messages in `error.details`. Authentication failures return HTTP 401; missing resources return HTTP 404. The interactive OpenAPI documentation is available at `/api/documentation` when the Swagger route is enabled.

## Stack and current status

- Composer requires PHP `^8.2` and Laravel `^12.0`; the lockfile resolves Laravel **12.69.3**.
- The PHPUnit suite runs against isolated SQLite `:memory:` and can be run with the command above.
- Latest verification: 31 backend tests/237 assertions and 31 frontend tests passed; frontend production build succeeded.
- Compose sets `CACHE_STORE=file` so API throttling does not depend on a MySQL `cache` table. Redis is optional and is not part of this stack.
- `composer validate --no-check-publish` passed with the missing-license metadata warning. `composer audit` found no known security advisories; it reports the transitive `doctrine/annotations` package as abandoned with no suggested replacement.
- The database seeder provides sample data and a test account; do not treat its credentials as production credentials.
- Telescope is installed for development and enabled only when `APP_ENV=local` and `TELESCOPE_ENABLED=true`; its dashboard is at `/telescope`.
- Low stock defaults to fewer than 10 items and is configurable with `LOW_STOCK_THRESHOLD`.
- The frontend must be cloned as a sibling directory named `frontend-legacy-vue2` for the integrated Compose build context.
- `.env.example` still needs a manual variable/credential review before using this setup outside local development.
