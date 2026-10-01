<?php

namespace Tests\Feature;

use Tests\TestCase;

class CorsTest extends TestCase
{
    /**
     * @dataProvider localFrontendOrigins
     */
    public function test_api_preflight_allows_local_frontend_json_bearer_requests($origin)
    {
        $response = $this->call('OPTIONS', '/api/health', [], [], [], [
            'HTTP_ORIGIN' => $origin,
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'authorization,content-type,x-csrf-token',
        ]);

        $response->assertStatus(204);
        $response->assertHeader('Access-Control-Allow-Origin', $origin);
        $response->assertHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $allowedHeaders = strtolower($response->headers->get('Access-Control-Allow-Headers'));
        $this->assertStringContainsString('authorization', $allowedHeaders);
        $this->assertStringContainsString('content-type', $allowedHeaders);
        $this->assertStringContainsString('x-csrf-token', $allowedHeaders);
    }

    public function localFrontendOrigins()
    {
        return [
            ['localhost frontend' => 'http://localhost:5173'],
            ['127.0.0.1 frontend' => 'http://127.0.0.1:5173'],
        ];
    }
}
