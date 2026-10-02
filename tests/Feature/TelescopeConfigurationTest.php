<?php

namespace Tests\Feature;

use Tests\TestCase;

class TelescopeConfigurationTest extends TestCase
{
    public function test_telescope_is_disabled_in_the_testing_environment(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertFalse((bool) config('telescope.enabled'));
        $this->get('/telescope')->assertNotFound();
    }
}
