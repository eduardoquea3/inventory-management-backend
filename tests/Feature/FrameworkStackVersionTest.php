<?php

namespace Tests\Feature;

use Tests\TestCase;

class FrameworkStackVersionTest extends TestCase
{
    public function test_runtime_php_and_laravel_versions_meet_the_migration_target(): void
    {
        $this->assertGreaterThanOrEqual(80200, PHP_VERSION_ID);
        $this->assertSame(12, (int) explode('.', app()->version())[0]);
        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertFalse((bool) config('telescope.enabled'));
    }
}
