<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegacySmokeTest extends TestCase
{
    public function test_health_endpoint_exists()
    {
        $response = $this->get('/api/health');
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.database', 'connected');
    }

    // Legacy issue: tests are superficial and do not validate business rules.
}
