<?php

namespace Tests\Feature;

use Tests\TestCase;

class FrameworkStackVersionTest extends TestCase
{
    public function test_runtime_php_and_laravel_versions_meet_the_migration_target(): void
    {
        $this->assertGreaterThanOrEqual(80200, PHP_VERSION_ID);
        $this->assertSame(12, (int) explode('.', app()->version())[0]);
    }
}
