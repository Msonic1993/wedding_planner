<?php

namespace Tests\Feature;

use Tests\TestCase;

class WebRoutesTest extends TestCase
{
    public function test_root_returns_api_metadata_instead_of_laravel_welcome_page(): void
    {
        $response = $this->getJson('/');

        $response
            ->assertOk()
            ->assertJsonPath('name', config('app.name'))
            ->assertJsonPath('version', 'v1')
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('documentation', url('/docs/api-docs.json'));
    }
}
