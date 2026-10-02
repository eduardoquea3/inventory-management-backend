<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CatalogCache
{
    private const VERSION_KEY = 'inventory:catalog-cache-version';

    public static function remember(string $resource, array $parameters, callable $callback): mixed
    {
        $version = Cache::get(self::VERSION_KEY, 'initial');
        $key = 'inventory:catalog:' . $version . ':' . $resource . ':' . hash('sha256', serialize($parameters));

        return Cache::remember($key, now()->addMinutes(5), $callback);
    }

    public static function invalidate(): void
    {
        Cache::forever(self::VERSION_KEY, (string) Str::uuid());
    }
}
