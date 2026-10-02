<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheStoreConfigurationTest extends TestCase
{
    public function test_file_cache_store_can_write_and_read_without_a_database_cache_table(): void
    {
        $this->assertSame(storage_path('framework/cache/data'), config('cache.stores.file.path'));

        $key = 'cache-store-regression-'.uniqid('', true);

        Cache::store('file')->put($key, 'cached value', 60);

        $this->assertSame('cached value', Cache::store('file')->get($key));
    }
}
