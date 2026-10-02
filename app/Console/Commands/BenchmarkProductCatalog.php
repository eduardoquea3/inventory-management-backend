<?php

namespace App\Console\Commands;

use App\Services\ProductService;
use App\Support\CatalogCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BenchmarkProductCatalog extends Command
{
    protected $signature = 'catalog:benchmark {--per-page=100}';
    protected $description = 'Compare the legacy N+1 product listing with the optimized, cached listing.';

    public function handle(ProductService $products): int
    {
        $perPage = max(1, min(100, (int) $this->option('per-page')));
        $legacy = $this->measure(function () use ($perPage) {
            $rows = DB::table('products')->orderByDesc('created_at')->orderByDesc('id')->limit($perPage)->get();
            foreach ($rows as $row) {
                if ($row->category_id !== null) {
                    DB::table('categories')->where('id', $row->category_id)->first();
                }
                DB::table('stock_movements')->where('product_id', $row->id)->count();
            }
        });

        CatalogCache::invalidate();
        $optimized = $this->measure(fn () => $products->paginate([
            'page' => 1,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ], $perPage));
        $cacheHit = $this->measure(fn () => $products->paginate([
            'page' => 1,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ], $perPage));

        $this->table(['Scenario', 'Rows', 'SQL queries', 'Elapsed ms'], [
            ['Legacy N+1 pattern', $perPage, $legacy['queries'], $legacy['milliseconds']],
            ['Optimized uncached listing', $perPage, $optimized['queries'], $optimized['milliseconds']],
            ['Optimized cache hit', $perPage, $cacheHit['queries'], $cacheHit['milliseconds']],
        ]);
        $this->line('Dataset: ' . DB::table('products')->count() . ' products, ' . DB::table('categories')->count() . ' categories, ' . DB::table('stock_movements')->count() . ' movements.');
        $this->comment('The legacy row-access pattern is reconstructed from the pre-refactor controller; timings are environment-specific.');

        return self::SUCCESS;
    }

    private function measure(callable $callback): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $startedAt = hrtime(true);
        $callback();
        $milliseconds = round((hrtime(true) - $startedAt) / 1_000_000, 2);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        return ['queries' => $queries, 'milliseconds' => $milliseconds];
    }
}
