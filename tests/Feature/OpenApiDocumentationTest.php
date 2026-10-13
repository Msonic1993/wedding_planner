<?php

namespace Tests\Feature;

use Tests\TestCase;

class OpenApiDocumentationTest extends TestCase
{
    public function test_openapi_json_is_available_at_a_stable_path(): void
    {
        $this->get('/docs/api-docs.json')
            ->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonPath('openapi', '3.0.0');
    }
}
