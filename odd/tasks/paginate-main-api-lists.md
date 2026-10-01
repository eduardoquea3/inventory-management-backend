# Paginate Main API Lists

## Goal
Add frontend-usable pagination to the product and category list endpoints specified by the technical guide.

## User decisions
- Paginate only `GET /api/products` and `GET /api/categories`; leave stock movement history and dashboard collections unchanged in this task.
- Use Laravel's standard paginator response (`data`, `links`, `meta`) for both list endpoints.
- Accept `page` and `per_page`; default page size 15, maximum 100.

## Scope and assumptions
- Preserve current auth protection and existing product query filters and product item fields.
- Use deterministic newest-first ordering, adding an id tie-breaker if needed.
- Keep default JSON pagination envelope consistent between products/categories; this changes the existing category wrapper contract from `{categories: [...]}`.
- Update Swagger/OpenAPI annotations and generated artifact so frontend clients can inspect page parameters and responses.
- Do not add guide-listed price/stock filters or selectable sorting in this pagination-only unit; track those separately.

## Tasks
1. Add focused test-first coverage for page metadata, custom/default page size, boundary validation, and both list endpoints.
2. Implement pagination while preserving existing filters and product relation/count fields; update OpenAPI docs/spec.
3. Run focused tests, spec generation/validation, and live endpoint checks; report the response contract for frontend use.

## Acceptance checks
- Products and categories return Laravel paginator envelope with correct totals/current page/per-page and stable ordering.
- Default `per_page=15`, accept 1–100, reject out-of-range/non-integer values with JSON validation errors.
- Product filters and `category`/`total_movements` per row remain available.
- Swagger documents page/per_page and paginated responses.
- Stock movement history and dashboard payloads remain unchanged.

## Evidence
- Guide: `Backend-Guia-Prueba-Tecnica.md` calls for pagination in main listings; product endpoint explicitly lists pagination.
- Exploration task `mupuo791-8-preh` mapped list responses and noted current API returns arrays / `{categories: [...]}` with no existing pagination convention.
- User approved standard Laravel paginator contract, product+category scope, default page size 15, max 100. Changes to the categories response envelope are intentional for consistency.
- Focused tests: `podman-compose exec -T app vendor/bin/phpunit tests/Feature/PaginationTest.php` passes (4 tests, 44 assertions). Initial test attempts returned 500 because `withoutMiddleware('auth')` replaced Laravel's auth manager with a test stub; tests now disable `LegacyTokenAuth` by class, preserving the limiter's AuthManager.
- `ProductController` now uses bound query-builder filters for existing `q`, `category_id`, and `status`, paginates deterministically, and preserves `category` and `total_movements`; `CategoryController` returns the same Laravel paginator contract.
- Live authenticated requests for page 2 with `per_page=2` returned 2 items each; products report 10,000 total / 5,000 pages for size 2, categories 100 total / 50 pages.
- OpenAPI generation passed and lists `page`/`per_page` for both list endpoints. Stock movement history and dashboard were not changed. Guide-listed price/stock filters and custom sorting remain future work.
- No commit made.
