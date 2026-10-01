<?php

namespace Tests\Feature;

use Tests\TestCase;

class SwaggerDocumentationTest extends TestCase
{
    public function test_swagger_ui_and_openapi_document_are_available(): void
    {
        $this->get('/api/documentation')->assertOk();

        $this->get('/docs')
            ->assertOk()
            ->assertJsonPath('openapi', '3.0.0');
    }

    public function test_openapi_documents_all_api_routes_and_legacy_auth(): void
    {
        $document = json_decode(file_get_contents(storage_path('api-docs/api-docs.json')), true);

        $this->assertIsArray($document);
        $operationCount = array_sum(array_map('count', $document['paths']));
        $this->assertSame(17, $operationCount);
        $this->assertArrayNotHasKey('security', $document);
        $this->assertSame([], $document['paths']['/api/login']['post']['security']);
        $this->assertSame([], $document['paths']['/api/health']['get']['security']);
        $this->assertSame([['LegacyTokenAuth' => []]], $document['paths']['/api/products']['get']['security']);
        $this->assertSame('http', $document['components']['securitySchemes']['LegacyTokenAuth']['type']);
        $this->assertSame('bearer', $document['components']['securitySchemes']['LegacyTokenAuth']['scheme']);
    }
}
