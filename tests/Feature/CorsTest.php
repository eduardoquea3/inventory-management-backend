<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CorsTest extends TestCase
{
    #[DataProvider('localFrontendOrigins')]
    public function test_api_preflight_allows_local_frontend_json_bearer_requests(string $origin)
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

    public static function localFrontendOrigins(): array
    {
        return [
            ['http://localhost:5173'],
            ['http://127.0.0.1:5173'],
            ['http://localhost:8080'],
            ['http://127.0.0.1:8080'],
        ];
    }
}
